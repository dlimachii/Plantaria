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
- `DB_PASSWORD=plantaria`
- `PLANTARIA_ADMIN_HANDLE`
- `PLANTARIA_ADMIN_EMAIL`
- `PLANTARIA_ADMIN_PASSWORD`
- `NOMINATIM_BASE_URL`
- `NOMINATIM_USER_AGENT`

## Datos demo

El seeder crea una cuenta admin configurable por entorno, una cuenta demo y registros alrededor de Barcelona.

Cuenta demo para Android:

```text
handle: plantaria_demo
password: PlantariaDemo1
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

- `q`: busqueda por ID publico, nombre provisional, nombre comun validado o nombre cientifico.
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
- cola de moderacion;
- verificacion y rechazo de registros;
- gestion de flags;
- gestion de usuarios para `ADMIN`;
- edicion avanzada de registros para `ADMIN`.

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
24 tests, 113 assertions
```

La suite cubre auth, subida de fotos, registros, observaciones, flags, geocodificacion, panel web, autorizacion API, seeder demo y filtro geoespacial.
