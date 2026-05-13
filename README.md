# Plantaria

Plantaria es el codigo final del TFG: una app Android para registrar plantas geolocalizadas con foto, mapa, observaciones y actividad de usuario. El proyecto incluye un backend Laravel con API REST, panel web de administracion, moderacion, analitica y estimacion de recursos/CO2 del servidor.

La variante Android `prod` apunta por defecto a:

```text
https://api.dlimachii.com/api/
```

El panel web de produccion, si el VPS sigue activo, esta en:

```text
https://api.dlimachii.com/admin
```

## Estructura

```text
android/        Cliente Android Kotlin + Jetpack Compose + MapLibre
backend/        API Laravel, Sanctum, panel admin, moderacion, analitica y tests
analytics/      Scripts Python/pandas usados por el panel de analitica
BBDD/           Exportacion SQL demo de la base de datos para revision en GitHub
deploy/vps/     Despliegue opcional de referencia con Docker Compose y Caddy
docs/           Referencia tecnica, API y despliegue
scripts/        Instalacion, validacion y apoyo para probar el APK
TFG/            Material auxiliar de defensa subido aparte del codigo
compose.yaml    PostgreSQL/PostGIS local para desarrollo o pruebas
```

No se incluyen dependencias generadas ni secretos: `vendor/`, `node_modules/`, `.gradle/`, builds Android, `.env`, bases SQLite locales, logs y storage publico estan ignorados por Git.

## Funcionalidades

- Registro e inicio de sesion en Android contra API Laravel con tokens Sanctum.
- Mapa MapLibre con registros de plantas, marcadores, GPS, busqueda y ficha de detalle.
- Creacion de reportes con foto, coordenadas, descripcion y nombre provisional.
- Observaciones temporales sobre registros existentes.
- Perfil y actividad de usuario.
- Panel admin web para dashboard, moderacion, usuarios, flags y asistente interno.
- Bloque de recursos del servidor en `/admin`: CPU, memoria, disco, energia estimada y CO2 estimado.
- Analitica auxiliar con Python/pandas.

## Requisitos

- PHP 8.3 o superior.
- Composer.
- Node.js y npm.
- PostgreSQL con PostGIS, o Docker para levantarlo con `compose.yaml`.
- Python 3 con `venv`.
- JDK 17.
- Android SDK instalado para compilar APKs.
- Android Debug Bridge (`adb`) si se quiere instalar en movil fisico.

## Instalacion rapida

Desde la raiz del repositorio:

```bash
chmod +x scripts/install_project.sh
./scripts/install_project.sh
```

El script instala dependencias de backend, Android y analitica:

- `composer install` en `backend/`.
- `npm ci` en `backend/`.
- crea `backend/.env` desde `backend/.env.example` si no existe.
- genera `APP_KEY` si falta.
- prepara `php artisan storage:link`.
- resuelve Gradle con `android/gradlew --version`.
- crea `analytics/.venv` e instala `analytics/requirements.txt`.

Opciones utiles:

```bash
./scripts/install_project.sh --build-android
./scripts/install_project.sh --migrate
./scripts/install_project.sh --skip-android
./scripts/install_project.sh --skip-backend
./scripts/install_project.sh --skip-analytics
```

`--migrate` requiere PostgreSQL funcionando y estas variables configuradas en `backend/.env`:

```text
PLANTARIA_ADMIN_PASSWORD=
PLANTARIA_DEMO_PASSWORD=
PLANTARIA_USER_PASSWORD=
PLANTARIA_MOD_PASSWORD=
```

Las contrasenas reales no se publican en el repositorio.

## Backend

Arranque local recomendado:

```bash
docker compose up -d postgis
cd backend
composer install
npm ci
cp .env.example .env
php artisan key:generate
# Rellenar antes en .env: PLANTARIA_ADMIN_PASSWORD, PLANTARIA_DEMO_PASSWORD,
# PLANTARIA_USER_PASSWORD y PLANTARIA_MOD_PASSWORD.
php artisan migrate --seed
php artisan storage:link
php artisan serve --host=0.0.0.0 --port=8000
```

URLs locales:

```text
API:        http://127.0.0.1:8000/api/
Admin web:  http://127.0.0.1:8000/admin
```

Tests:

```bash
cd backend
php artisan test
```

Analitica pandas:

```bash
cd backend
php artisan plantaria:analytics:build
```

## Android

La variante principal es `prod`. Usa el nombre `Plantaria`, habilita el selector de vista de mapa y apunta a `https://api.dlimachii.com/api/`.

Compilar APK `prod` debug:

```bash
cd android
./gradlew :app:assembleProdDebug
```

Salida:

```text
android/app/build/outputs/apk/prod/debug/app-prod-debug.apk
```

Instalacion por script Bash:

```bash
scripts/install_debug_apk.sh
```

En Windows/PowerShell:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/install_debug_apk.ps1
```

## Configuracion

El backend usa `backend/.env.example` como plantilla. Para produccion real se uso el dominio:

```text
APP_URL=https://api.dlimachii.com
CORS_ALLOWED_ORIGINS=http://localhost,http://127.0.0.1:8000,http://localhost:3000,https://dlimachii.com,https://api.dlimachii.com
```

El archivo `.env` no debe subirse a Git. Si se despliega en otro servidor, hay que cambiar `APP_URL`, credenciales de base de datos, claves y passwords de demo/admin.

## Base de datos

La base de datos real del proyecto es PostgreSQL/PostGIS. En local se levanta con
`compose.yaml` y Docker guarda los datos en un volumen persistente, no en un unico
archivo dentro del repositorio.

Para que pueda revisarse directamente desde GitHub, el repositorio incluye una
exportacion SQL demo en:

```text
BBDD/plantaria_demo.sql
```

Ese SQL contiene los datos demo por defecto del seeder: usuarios demo, registros de
plantas y observaciones iniciales. El esquema real completo esta en
`backend/database/migrations/`.

## Documentacion

- `BBDD/README.md`: explicacion rapida de donde esta la BBDD y que contiene el SQL demo.
- `docs/REFERENCIA_TECNICA.md`: arquitectura y puntos tecnicos principales.
- `docs/API.md`: endpoints principales y ejemplos de payload.
- `docs/DEPLOY_VPS.md`: despliegue opcional de referencia.
- `android/README.md`: detalles del cliente Android.
- `backend/README.md`: detalles del backend.

## Validacion

Comandos principales:

```bash
./scripts/validate_project.sh
cd backend && php artisan test
cd android && ./gradlew :app:assembleProdDebug
bash -n scripts/*.sh
```

Comprobaciones concretas de esta version:

- `backend/app/Services/ServerFootprintSnapshot.php` alimenta el bloque de recursos y CO2 del dashboard.
- `backend/resources/views/admin/dashboard.blade.php` muestra recursos del servidor y CO2 estimado.
- `android/app/build.gradle.kts` configura `prod` con `https://api.dlimachii.com/api/`.
- `android/app/src/main/java/com/plantaria/app/ui/screens/MapScreen.kt` incluye el selector de vista de mapa.

## Seguridad

- No subir `backend/.env`, `android/local.properties`, `backend/vendor/`, `backend/node_modules/`, builds Android ni storage publico.
- Las passwords de usuarios demo/admin se configuran en entorno.
- El asistente SQL admin esta limitado a lectura.
- CORS se configura por variable de entorno y en produccion queda acotado a dominios concretos.
