# Plantaria Backend

Backend Laravel de `Plantaria`, una plataforma colaborativa para registrar plantas geolocalizadas, añadir observaciones temporales y moderar la calidad de los datos.

## Stack

- PHP 8.3+.
- Laravel 13.
- Laravel Sanctum para tokens de la app Android.
- PostgreSQL/PostGIS como base de datos local principal.
- Panel web Laravel bajo `/admin`.

## Arranque local

Desde la raiz del repositorio:

```bash
docker compose up -d postgis
```

En `backend/`:

```bash
composer install
cp .env.example .env
php artisan key:generate
# Rellenar antes en .env las variables PLANTARIA_*_PASSWORD.
php artisan migrate --seed
php artisan storage:link
php artisan serve --host=0.0.0.0 --port=8000
```

Para la prueba movil se puede usar directamente:

```bash
../scripts/start_mobile_stack.sh
```

Ese script levanta PostGIS, ejecuta migraciones y seeders, crea el enlace de storage e inicia Laravel con limites de subida adecuados para fotos reales.

## Variables principales

La configuracion base vive en `.env.example`.

Variables relevantes:

- `DB_CONNECTION=pgsql`
- `DB_HOST=127.0.0.1`
- `DB_DATABASE=plantaria`
- `DB_USERNAME=plantaria`
- `DB_PASSWORD=plantaria` para el `compose.yaml` local; cambiarlo en produccion.
- `PLANTARIA_ADMIN_HANDLE`
- `PLANTARIA_ADMIN_EMAIL`
- `PLANTARIA_ADMIN_PASSWORD`
- `NOMINATIM_BASE_URL`
- `NOMINATIM_USER_AGENT`
- `PLANTARIA_ANALYTICS_PYTHON`
- `OLLAMA_BASE_URL`
- `OLLAMA_MODEL`

## Datos demo

El seeder crea cuentas de prueba por rol, una cuenta demo con registros y datos alrededor de Barcelona.

Cuentas de prueba:

```text
USER  · plantaria_user
MOD   · plantaria_mod
ADMIN · plantaria_admin
```

Cuenta demo con datos cargados: `plantaria_demo`.

Las contraseñas de demo se configuran en `.env` y no se incluyen en Git:

```text
PLANTARIA_ADMIN_PASSWORD=...
PLANTARIA_DEMO_PASSWORD=...
PLANTARIA_USER_PASSWORD=...
PLANTARIA_MOD_PASSWORD=...
```

## API principal

Rutas publicas:

- `POST /api/auth/register`
- `POST /api/auth/login`
- `GET /api/records`
- `GET /api/records/{publicId}`
- `GET /api/profiles/{handle}`
- `GET /api/geocoding/search`

Rutas autenticadas:

- `GET /api/auth/me`
- `POST /api/auth/logout`
- `GET /api/me/activity`
- `PATCH /api/profile`
- `POST /api/uploads/photos`
- `POST /api/records`
- `POST /api/records/{publicId}/observations`
- `POST /api/flags`

La referencia práctica de endpoints está en `../docs/API.md`.

Rutas administrativas API:

- `GET /api/admin/analytics/summary`
- `GET /api/admin/analytics/trends`
- `GET /api/admin/analytics/top-searches`
- `GET /api/admin/moderation/pending`
- `POST /api/admin/moderation/records/{publicId}/verify`
- `GET /api/admin/moderation/flags`
- `POST /api/admin/moderation/flags/{uid}/resolve`
- `GET /api/admin/users`
- `GET /api/admin/users/{handle}`
- `PATCH /api/admin/users/{handle}`
- `POST /api/admin/users/{handle}/ban`
- `DELETE /api/admin/users/{handle}`

## Filtros de registros

`GET /api/records` acepta:

- `q`: busqueda por nombre provisional, nombre comun validado o nombre cientifico.
- `status`: `pending`, `verified` o `rejected`.
- `limit`: entre `1` y `100`.
- `latitude`, `longitude`, `radius_km`: filtro por radio.

En PostgreSQL usa funciones PostGIS (`ST_DWithin` y `ST_Distance`). En tests sqlite se aplica un fallback matematico para mantener la suite rapida.

Ejemplo:

```text
GET /api/records?latitude=41.3851&longitude=2.1734&radius_km=5&limit=20
```

La respuesta incluye `distance_km` cuando se usa el filtro por radio.

## Panel web

Panel disponible en:

```text
http://127.0.0.1:8000/admin
```

Incluye:

- login para `MOD` y `ADMIN`;
- dashboard con analitica visual;
- snapshot calculado con Python+pandas cuando se ejecuta `php artisan plantaria:analytics:build`;
- asistente para `ADMIN` con consultas directas seguras a BBDD y Ollama local como apoyo para preguntas abiertas con contexto pandas;
- cola de moderacion;
- verificacion y rechazo de registros;
- gestion de flags;
- gestion de usuarios para `ADMIN`;
- edicion avanzada de registros para `ADMIN`.

## Analitica Python + pandas

Laravel exporta datasets de la app y ejecuta el script `../analytics/build_admin_analytics.py`.

```bash
php artisan plantaria:analytics:build
```

Salida:

```text
storage/app/analytics/input/*.csv
storage/app/analytics/output/admin_dashboard.json
```

El dashboard `/admin` muestra ese JSON si existe. Desde el panel, un `ADMIN` tambien puede pulsar `Actualizar pandas`. El asistente puede responder consultas directas acotadas aunque este snapshot todavia no exista.

Si se quiere preparar el entorno Python:

```bash
cd ../analytics
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
cd ../backend
php artisan config:clear
php artisan plantaria:analytics:build
```

Si aparece `ModuleNotFoundError: No module named 'pandas'`, Laravel esta usando `python3` del sistema en vez del venv. Comprobar que `.env` contiene `PLANTARIA_ANALYTICS_PYTHON=../analytics/.venv/bin/python` y ejecutar `php artisan config:clear` antes de repetir el comando.

## Asistente admin y Ollama local

El asistente del panel vive en `/admin/assistant` y no envia datos a una API externa. Primero intenta consultas Laravel cerradas para preguntas administrativas conocidas, por ejemplo usuarios con mas observaciones o plantas verificadas sin nombre cientifico. Para preguntas abiertas usa el snapshot pandas como contexto y el endpoint local de Ollama:

```text
OLLAMA_BASE_URL=http://127.0.0.1:11434
OLLAMA_MODEL=llama3.2:1b
```

Preparacion orientativa:

```bash
ollama pull llama3.2:1b
ollama serve
```

## Fotos

Las fotos se suben con `POST /api/uploads/photos` y se guardan en `storage/app/public`.

En una instalacion limpia hay que ejecutar:

```bash
php artisan storage:link
```

Durante pruebas con movil real, `scripts/start_mobile_stack.sh` arranca PHP con limites de subida ampliados.

## Geocodificacion

`GET /api/geocoding/search` actua como proxy cacheado de Nominatim para que Android no dependa directamente del proveedor.

La configuracion esta en `config/services.php` y se controla con:

```text
NOMINATIM_BASE_URL
NOMINATIM_USER_AGENT
```

## Validacion

```bash
php artisan test
```

Desde la raiz del repositorio tambien se puede ejecutar:

```bash
./scripts/validate_project.sh
```

Estado reciente:

```text
38 tests, 176 assertions
```

La suite cubre auth, subida de fotos, registros, observaciones, actividad propia de usuario, flags, geocodificacion, panel web, autorizacion API, seeder demo, export de analitica y filtro geoespacial.
