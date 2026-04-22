# Entorno y versiones

## Estado actual

Ya hay proyecto arrancado y una primera base funcional del backend en el repo.

Este archivo pasa a combinar dos cosas:

- stack objetivo del proyecto;
- estado observado del entorno real en esta sesión.

## Stack objetivo recomendado

### Cliente principal

- Android:
  - Kotlin;
  - Jetpack Compose;
  - CameraX para captura de foto;
  - Room para caché local básica si hace falta;
  - WorkManager para tareas en segundo plano si aparecen reintentos o colas.

### Backend

- PHP 8.4 como punto de arranque conservador;
- Laravel 13;
- Laravel Sanctum para autenticación de app móvil y panel web si se reutiliza el mismo backend.

### Analítica complementaria

- Python 3.12.x como apoyo analítico;
- `pandas` como librería prevista para tratamiento de datos e informes;
- `Ollama` como opción local futura para consultas administrativas en lenguaje natural.

### Base de datos

- PostgreSQL 18.x;
- PostGIS 3.6.x;
- extensión `pg_trgm` y búsqueda de texto de PostgreSQL para nombres y búsquedas tolerantes.

Decisión operativa:

- PostgreSQL/PostGIS es el motor real del proyecto;
- `sqlite` queda solo para tests o fallback mínimo de desarrollo, no como objetivo principal.

### Mapas y geolocalización

- OpenStreetMap como base cartográfica;
- Nominatim 5.x para búsqueda textual de lugares y geocodificación;
- Leaflet 1.9.4 si se hace mapa web en panel o cliente web futuro;
- capa móvil Android con MapLibre Native Android.

## Herramientas auxiliares recomendadas

- Docker Compose para levantar backend, base de datos y servicios auxiliares.
- OpenAPI 3.1 para documentar la API.
- PHPUnit o Pest para pruebas del backend.
- Postman, Bruno o Insomnia para probar endpoints durante desarrollo.
- GitHub para control de versiones y, si da tiempo, CI básica.

## Estado observado del entorno local en esta sesión

- `python3` disponible: 3.12.3
- `pip3` disponible
- `php` disponible: 8.3.6
- `composer` disponible
- `psql` disponible: 16.13
- `docker` disponible: 29.1.3
- `docker compose` disponible: 2.40.3
- `gradle` disponible globalmente: 4.4.1, instalado por `apt`; es antiguo y no debe condicionar el proyecto Android, que deberá usar Gradle wrapper propio
- `kotlinc` no estaba instalado
- `sdkmanager` disponible: 20.0
- `adb` disponible: 37.0.0
- `emulator` disponible: 36.5.10

## Avance real del entorno

- Se pudo completar `composer install` en `backend/`.
- Se instaló Sanctum mediante `php artisan install:api`.
- Se generó clave local de Laravel.
- Se levantó PostgreSQL/PostGIS real con Docker Compose.
- Se migró el backend contra PostgreSQL/PostGIS real.
- Se ejecutó el seeder de la cuenta admin inicial.
- El backend pasa tests básicos.

## Estado de instalación Android en esta sesión

La instalación de toolchain Android en Ubuntu/WSL quedó recuperada y validada.

Comandos ya ejecutados:

- `sudo apt update`
- `sudo apt install -y unzip zip gradle`
- `mkdir -p "$HOME/Android/Sdk/cmdline-tools"`
- descarga y descompresión de `commandlinetools-linux-14742923_latest.zip`
- movimiento de `cmdline-tools` a `"$HOME/Android/Sdk/cmdline-tools/latest"`
- corrección de `~/.bashrc` tras una línea `PATH` rota por un salto de línea accidental;
- aceptación de licencias Android SDK;
- instalación de:
  - `platform-tools`
  - `cmdline-tools;latest`
  - `platforms;android-36`
  - `build-tools;36.0.0`
  - `emulator`
  - `system-images;android-36;google_apis;x86_64`
- creación del AVD `plantaria-api36`.

Validaciones realizadas:

- `sdkmanager --version`: 20.0
- `adb version`: 37.0.0
- `gradle -v`: 4.4.1 global de `apt`
- `avdmanager list avd`: muestra `plantaria-api36`
- `emulator -list-avds`: muestra `plantaria-api36`

Notas:

- Se eliminó una copia duplicada `cmdline-tools/latest-2` que provocaba avisos de ruta inconsistente.
- Las líneas de `fzf` en `~/.bashrc` quedaron protegidas con comprobación de existencia para evitar errores al cargar la shell.
- Al ejecutar `emulator -list-avds` dentro de la herramienta puede aparecer un aviso `setsockopt: Operation not permitted`; aun así lista el AVD correctamente. Es un aviso del entorno de ejecución, no un fallo de instalación.

## Estado del cliente Android

Ya existe `android/` como proyecto Android compilable.

Versiones usadas:

- Gradle wrapper: 9.3.1
- Android Gradle Plugin: 9.1.1
- Kotlin/Compose compiler: 2.3.10
- Compose BOM: 2026.03.00
- Navigation Compose: 2.9.7
- Activity Compose: 1.13.0
- DataStore Preferences: 1.2.1
- Lifecycle Runtime/ViewModel Compose: 2.10.0
- MapLibre Native Android: 13.0.2
- `compileSdk`: 36
- `targetSdk`: 36
- `minSdk`: 26

Configuración de API local Android:

- `BuildConfig.PLANTARIA_API_BASE_URL` apunta por defecto a `http://10.0.2.2:8000/api/`.
- Esa URL está pensada para emulador Android; `10.0.2.2` apunta al host que ejecuta Laravel.
- Para móvil físico, la pantalla de acceso permite editar y guardar la URL de API.
- En móvil físico por Wi-Fi debe usarse la IP LAN del PC, por ejemplo `http://10.4.20.61:8000/api/` si esa IP sigue siendo válida.
- Al ejecutar Laravel dentro de WSL2, puede hacer falta publicar el puerto desde Windows hacia la IP WSL con `netsh interface portproxy`.
- Alternativa para móvil físico por USB: `adb reverse tcp:8000 tcp:8000` y URL `http://127.0.0.1:8000/api/` en la app.
- El manifest permite cleartext traffic para desarrollo local con HTTP.
- El manifest ya declara permisos de ubicación fina/aproximada y cámara; la app solicita ubicación y cámara en runtime desde la pantalla de acciones.
- La captura directa de cámara usa `ActivityResultContracts.TakePicture` y `androidx.core.content.FileProvider` con cache interna de la app.

Notas:

- Con AGP 9.1 no se aplica `org.jetbrains.kotlin.android`; Kotlin viene integrado en el plugin Android.
- La compilación se validó con `./gradlew :app:assembleDebug`.
- En el sandbox de Codex CLI, el Gradle wrapper puede fallar si necesita escribir locks en `~/.gradle`; en ese caso hay que ejecutarlo con permisos fuera del sandbox o configurar `GRADLE_USER_HOME` dentro del workspace.
- El APK debug aumenta de tamaño al incluir las librerías nativas de MapLibre; se observó un APK de unos 78M tras integrar el mapa.
- El Gradle global instalado por `apt` es antiguo y no debe usarse para compilar el proyecto.
- Se creó `~/.gradle` para que el wrapper pueda funcionar de forma normal.
- Para instalar en móvil físico: `adb install -r app/build/outputs/apk/debug/app-debug.apk`.
- Para que Laravel sirva imágenes subidas desde `storage/app/public`, ejecutar `php artisan storage:link` en una instalación limpia.

## Variables locales relevantes ya previstas

- `PLANTARIA_ADMIN_HANDLE`
- `PLANTARIA_ADMIN_NAME`
- `PLANTARIA_ADMIN_EMAIL`
- `PLANTARIA_ADMIN_PASSWORD`

## Almacenamiento de imágenes

Opciones razonables:

- desarrollo inicial: disco local del backend;
- entorno algo más serio: almacenamiento compatible con S3;
- si se quiere entorno local reproducible: MinIO como opción práctica.

## Notas importantes de arquitectura

- Los tiles públicos de OpenStreetMap no deben asumirse como solución de producción estable ni con SLA.
- El cliente Android usa de momento `https://demotiles.maplibre.org/style.json`; sirve para desarrollo y demo, pero no debe tratarse como infraestructura final con SLA.
- Las versiones exactas del cliente Android quedan cerradas en `android/app/build.gradle.kts` y resumidas en este archivo.

## Regla principal

Cada vez que aparezca una decisión real de entorno, instalación o compatibilidad, debe anotarse aquí con precisión.
