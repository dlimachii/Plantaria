# Plantaria

Plantaria es el codigo final del TFG: una app Android para registrar plantas geolocalizadas con foto, mapa, observaciones y actividad de usuario, junto a un backend Laravel con API REST, panel web de administracion, moderacion, analitica y estimacion de huella de carbono del servidor.

Esta carpeta junta la version de produccion del VPS con la version final Android local. La build Android `prod` apunta por defecto a:

```text
https://api.dlimachii.com/api/
```

El panel web de produccion, si el VPS sigue activo, esta en:

```text
https://api.dlimachii.com/admin
```

## Contenido

```text
backend/       API Laravel, Sanctum, panel admin, moderacion, analitica y tests
android/       Cliente Android Kotlin + Jetpack Compose + MapLibre
analytics/     Scripts Python/pandas usados por el panel de analitica
scripts/       Scripts de instalacion, validacion, APK y utilidades de entrega
deploy/vps/    Despliegue opcional de referencia para VPS con Docker/Caddy
docs/          Documentacion tecnica, API, demo y validacion movil
Contexto/      Notas historicas y contexto tecnico del proyecto
compose.yaml   PostgreSQL/PostGIS local para desarrollo o pruebas
```

No se incluyen dependencias generadas ni secretos: `vendor/`, `node_modules/`, `.gradle/`, builds Android, `.env`, bases SQLite locales, logs y storage publico estan ignorados por Git.

## Funcionalidades principales

- Registro/login Android contra API Laravel con tokens Sanctum.
- Mapa MapLibre con registros de plantas, marcadores, clustering sencillo, GPS, busqueda y ficha de detalle.
- Selector compacto de vista de mapa en Android para alternar entre el estilo actual y OSM estandar.
- Creacion de reportes con foto, coordenadas, descripcion y nombre provisional.
- Observaciones temporales sobre registros existentes.
- Perfil y actividad de usuario.
- Panel admin web para dashboard, moderacion, usuarios, flags y asistente interno.
- Bloque de huella digital/recursos en `/admin`: CPU, memoria, disco, energia estimada y CO2 estimado.
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

Desde la raiz de esta carpeta:

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
php artisan migrate --seed
php artisan storage:link
php artisan serve --host=0.0.0.0 --port=8000
```

URLs locales:

```text
API:         http://127.0.0.1:8000/api/
Admin web:   http://127.0.0.1:8000/admin
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

Tambien existen variantes de demo:

```bash
./gradlew :app:assembleDemoADebug
./gradlew :app:assembleDemoBDebug
./gradlew :app:assembleDemoCDebug
./gradlew :app:assembleDemoPJDebug
```

Instalacion por scripts:

```bash
scripts/install_debug_apk.sh
```

En Windows/PowerShell:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/install_debug_apk.ps1
```

## Configuracion de entorno

El backend usa `backend/.env.example` como plantilla. Para produccion real se uso el dominio:

```text
APP_URL=https://api.dlimachii.com
CORS_ALLOWED_ORIGINS=http://localhost,http://127.0.0.1:8000,http://localhost:3000,https://dlimachii.com,https://api.dlimachii.com
```

El archivo `.env` no debe subirse a Git. Si se despliega en otro servidor, cambiar `APP_URL`, credenciales de base de datos, claves y passwords de demo/admin.

## Despliegue opcional

No hace falta desplegar para revisar el TFG. La referencia de despliegue esta en:

```text
deploy/vps/
docs/DEPLOY_VPS.md
```

El proyecto final documenta el despliegue real usado con `api.dlimachii.com`, pero los certificados, `.env` real, base de datos, storage de fotos y credenciales no forman parte del codigo subido.

## Documentacion

- `docs/API.md`: endpoints principales.
- `docs/GUIA_DEMO.md`: guion de demostracion.
- `docs/CHECKLIST_VALIDACION_MOVIL.md`: pruebas sobre telefono fisico.
- `docs/MEMORIA_TFC.md`: base tecnica para la memoria.
- `docs/REFERENCIA_TECNICA.md`: resumen de arquitectura.
- `android/README.md`: detalles del cliente Android.
- `backend/README.md`: detalles del backend.
- `contexto_proyecto.md`: contexto amplio del estado final.

## Validacion

Comandos principales:

```bash
./scripts/validate_project.sh
cd backend && php artisan test
cd android && ./gradlew :app:assembleProdDebug
bash -n scripts/*.sh
```

Comprobaciones concretas de esta version final:

- `backend/app/Services/ServerFootprintSnapshot.php` existe y alimenta el bloque de huella de carbono del dashboard.
- `backend/resources/views/admin/dashboard.blade.php` muestra recursos del servidor y CO2 estimado.
- `android/app/build.gradle.kts` configura `prod` con `https://api.dlimachii.com/api/`.
- `android/app/src/main/java/com/plantaria/app/ui/screens/MapScreen.kt` incluye el selector de vista de mapa.

## Notas de seguridad

- No subir `backend/.env`, `android/local.properties`, `backend/vendor/`, `backend/node_modules/`, builds Android ni storage publico.
- Las passwords de usuarios demo/admin se configuran en entorno.
- El asistente SQL admin esta limitado a lectura.
- CORS se configura por variable de entorno y en produccion queda acotado a dominios concretos.
