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
- `Ollama` como opción local para consultas administrativas en lenguaje natural.

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

Estado actualizado: 2026-04-22 18:30 CEST.

- La pantalla de mapa Android solicita permisos de ubicación fina/aproximada al pulsar `Mi ubicación`.
- Si el permiso ya estaba concedido, intenta centrar el mapa automáticamente al entrar.
- Usa `LocationManager.getCurrentLocation` en Android R+ y última ubicación conocida como fallback.
- Mantiene las coordenadas manuales en formularios como alternativa cuando GPS/permisos no estén disponibles.

## Herramientas auxiliares recomendadas

- Docker Compose para levantar backend, base de datos y servicios auxiliares.
- OpenAPI 3.1 para documentar la API.
- PHPUnit o Pest para pruebas del backend.
- Postman, Bruno o Insomnia para probar endpoints durante desarrollo.
- GitHub para control de versiones y, si da tiempo, CI básica.

## Git y GitHub

Estado actualizado: 2026-04-22 17:30 CEST.

- Git está inicializado en la raíz del workspace, rama `main`.
- Identidad Git local del proyecto: `dlimachii <dlimachi@icloud.com>`.
- Se generó clave SSH local para GitHub en `~/.ssh/id_ed25519`.
- Fingerprint de la clave pública: `SHA256:2yuV33Jk6tvBm9eKjUJJ+zacTNU16XsvM/NKv6eZrNM`.
- Remoto `origin`: `git@github.com:dlimachii/Plantaria.git`.
- La rama `main` local sigue a `origin/main`.

## Panel web Laravel

Estado actualizado: 2026-04-30 16:20 CEST.

- Rutas web bajo `/admin`.
- Login web en `/admin/login` usando handle o email.
- Acceso restringido a usuarios con rol `mod` o `admin`.
- Dashboard básico en `/admin`.
- Cola de moderación en `/admin/moderation/pending`.
- Detalle y acciones de verificar/rechazar en `/admin/moderation/records/{publicId}`.
- Gestión de flags en `/admin/flags` para roles `mod` y `admin`.
- Gestión básica de usuarios en `/admin/users` para rol `admin`.
- Proxy de geocodificación en `/api/geocoding/search` para búsquedas de lugar desde Android.
- Dashboard visual en `/admin` con métricas y analítica de uso renderizada en servidor.
- Snapshot Python+pandas en `/admin` generado por `php artisan plantaria:analytics:build`.
- Entorno local Python de analítica preparado en `analytics/.venv`; `.env` local apunta `PLANTARIA_ANALYTICS_PYTHON=../analytics/.venv/bin/python`.
- Asistente local en `/admin/assistant`: primero consultas directas seguras de BBDD para preguntas conocidas; Ollama se usa si está disponible para preguntas abiertas con contexto pandas.
- Estilo de mapa Android configurable por `PLANTARIA_MAP_STYLE_URL` en `app/build.gradle.kts`.

## Prueba móvil real

Estado actualizado: 2026-04-30 17:15 CEST.

- `scripts/start_mobile_stack.sh` arranca ahora Laravel con:
  - `upload_max_filesize=20M`;
  - `post_max_size=24M`;
  - `memory_limit=512M`.
- `scripts/profile_app_performance.sh` permite perfilado rápido API/APK/ADB:
  - `PLANTARIA_PROFILE_RUNS` cambia iteraciones;
  - `PLANTARIA_PROFILE_BASE_URL` apunta a una API ya arrancada;
  - `PLANTARIA_PROFILE_PORT` cambia el puerto temporal si el script arranca Laravel.
- La ruta backend de subida de fotos acepta hasta `20 MB` para la prueba móvil.
- La app Android comprime/prepara la imagen antes de subirla para reducir fallos con fotos reales de cámara o galería.
- La pantalla de login ya no muestra el campo técnico de URL de API.
- Estado actualizado: 2026-05-10 20:47 CEST.
- La variante Android `prod` usa por defecto `https://api.dlimachii.com/api/`.
- La pantalla de acceso permite cambiar manualmente el servidor cuando se quiere probar backend local o un túnel temporal.
- Si el dispositivo conserva una URL local antigua (`127.0.0.1`, `10.0.2.2`, `localhost` o `0.0.0.0`) en `DataStore`, la app actual la descarta al arrancar y vuelve al servidor público por defecto.
- La combinación operativa recomendada para móvil físico sigue siendo:
  - compilar APK desde WSL;
  - instalar y ejecutar `adb reverse tcp:8000 tcp:8000` desde Windows PowerShell;
  - guardar `http://127.0.0.1:8000/api/` desde la pantalla de acceso si se quiere usar el backend local por USB;
  - usar `scripts/install_debug_apk.ps1` como flujo recomendado para el móvil físico.
- `scripts/install_debug_apk.sh` queda como alternativa solo si ADB detecta el teléfono desde WSL.

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

- `BuildConfig.PLANTARIA_API_BASE_URL` apunta por defecto a `https://api.dlimachii.com/api/`.
- Para emulador Android local, la pantalla de acceso permite editar y guardar `http://10.0.2.2:8000/api/`.
- Para móvil físico por USB, la pantalla de acceso permite editar y guardar `http://127.0.0.1:8000/api/` usando `adb reverse`.
- En móvil físico por Wi-Fi debe usarse la IP LAN del PC, por ejemplo `http://10.4.20.61:8000/api/` si esa IP sigue siendo válida.
- Al ejecutar Laravel dentro de WSL2, puede hacer falta publicar el puerto desde Windows hacia la IP WSL con `netsh interface portproxy`.
- El bootstrap remoto de servidor queda desactivado mientras no exista un `plantaria.json` real del proyecto.
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
- Para instalar en móvil físico en este equipo, usar Windows PowerShell:
  - `$Apk = wsl wslpath -w /home/aviddrianimachie/CEAC/Proyecto/android/app/build/outputs/apk/debug/app-debug.apk`;
  - `adb devices`;
  - `adb reverse tcp:8000 tcp:8000`;
  - `adb install -r $Apk`.
- Si la analítica pandas falla con `ModuleNotFoundError: No module named 'pandas'`, ejecutar `php artisan config:clear` en `backend/`; el `.env` local debe apuntar `PLANTARIA_ANALYTICS_PYTHON=../analytics/.venv/bin/python`.
- Para que Laravel sirva imágenes subidas desde `storage/app/public`, ejecutar `php artisan storage:link` en una instalación limpia.

## Variables locales relevantes ya previstas

- `PLANTARIA_ADMIN_HANDLE`
- `PLANTARIA_ADMIN_NAME`
- `PLANTARIA_ADMIN_EMAIL`
- `PLANTARIA_ADMIN_PASSWORD`
- `NOMINATIM_BASE_URL`
- `NOMINATIM_USER_AGENT`

## Almacenamiento de imágenes

Opciones razonables:

- desarrollo inicial: disco local del backend;
- entorno algo más serio: almacenamiento compatible con S3;
- si se quiere entorno local reproducible: MinIO como opción práctica.

Estado actualizado: 2026-04-22 18:16 CEST.

- El backend devuelve `primary_photo_url` y `photo_url` para registros y observaciones.
- Android usa esas URLs para pintar fotos reales en mapa/ficha.
- Si Laravel devuelve una URL con `localhost`, el cliente Android la normaliza usando la raíz de `BuildConfig.PLANTARIA_API_BASE_URL` o la URL editable guardada por el usuario.
- Para que las URLs funcionen en una instalación limpia, sigue siendo necesario ejecutar `php artisan storage:link` en `backend/`.

## Notas importantes de arquitectura

- Los tiles públicos de OpenStreetMap no deben asumirse como solución de producción estable ni con SLA.
- El cliente Android usa de momento `https://demotiles.maplibre.org/style.json`; sirve para desarrollo y demo, pero no debe tratarse como infraestructura final con SLA.
- Las versiones exactas del cliente Android quedan cerradas en `android/app/build.gradle.kts` y resumidas en este archivo.

## Revalidación local reciente

Estado actualizado: 2026-04-24 17:26 CEST.

Comandos ejecutados:

- `php artisan test` en `backend/`: 24 tests, 113 assertions, todo pasando.
- `./gradlew :app:assembleDebug` en `android/`: `BUILD SUCCESSFUL`.
- `bash -n scripts/start_mobile_stack.sh`: correcto.
- `bash -n scripts/install_debug_apk.sh`: correcto.
- `docker compose ps`: `plantaria-postgis` healthy.
- `php artisan migrate --seed --no-interaction`: sin migraciones pendientes y seed ejecutado.
- `curl -H 'Accept: application/json' 'http://127.0.0.1:8001/api/records?latitude=41.3851&longitude=2.1734&radius_km=8&limit=10'`: devuelve registros demo con `distance_km`.
- `curl -H 'Accept: application/json' 'http://127.0.0.1:8001/api/records?latitude=41.3851'`: devuelve errores JSON de validación para `longitude` y `radius_km`.
- `./scripts/validate_project.sh`: correcto; ejecuta sintaxis de scripts, `php artisan test`, `./gradlew :app:assembleDebug` y smoke HTTP contra PostGIS si `postgis` está en ejecución.

Notas del script integral:

- `SKIP_ANDROID_BUILD=1` salta la compilación Android.
- `SKIP_POSTGIS_SMOKE=1` salta la prueba HTTP contra PostgreSQL/PostGIS.
- `PLANTARIA_VALIDATE_PORT=8010` permite cambiar el puerto temporal de Laravel.
- `composer validate --no-check-publish`: correcto el 2026-04-24 17:34 CEST tras actualizar `composer.lock` por el cambio de metadata del paquete backend.
- `php artisan test` sube a 24 tests y 113 assertions el 2026-04-24 17:41 CEST tras añadir middleware de cuenta activa y tests de autorización API admin.
- `php artisan test` sube a 29 tests y 138 assertions el 2026-04-28 16:48 CEST tras añadir cuentas seedables por rol y tests del seeder.
- `php artisan test` sube a 33 tests y 157 assertions el 2026-04-28 17:24 CEST tras añadir actividad propia de usuario y tests de `/api/me/activity`.
- `php artisan test` sube a 37 tests y 168 assertions el 2026-04-28 17:37 CEST tras añadir export de analítica, snapshot pandas en dashboard y asistente Ollama opcional.
- `php artisan test` sube a 38 tests y 176 assertions el 2026-04-30 16:20 CEST tras añadir consultas directas seguras al asistente admin.
- `php artisan plantaria:analytics:build` correcto el 2026-04-30 16:20 CEST contra PostgreSQL/PostGIS local usando `analytics/.venv`.
- `scripts/profile_app_performance.sh` correcto el 2026-04-30 16:45 CEST con 3 iteraciones contra Laravel temporal en puerto 8021 y PostgreSQL/PostGIS local.

## Backup OneDrive

Estado actualizado: 2026-04-24 17:44 CEST.

- Script: `scripts/package_for_onedrive.sh`.
- Documentación: `docs/BACKUP_ONEDRIVE.md`.
- Destino por defecto detectado: `/ruta/a/PlantariaBackups`.
- Paquete real creado: `plantaria-backup-20260424-174446`.
- Contiene fuente comprimida, bundle Git, APK debug, `MANIFEST.txt` y `SHA256SUMS`.
- Validación de hashes ejecutada con `sha256sum -c SHA256SUMS`: correcta.
- Dump SQL no se incluye por defecto; se activa con `INCLUDE_DB_DUMP=1`.

Nota operativa:

- la primera ejecución de Gradle dentro del sandbox falló porque intentó escribir locks en `/home/aviddrianimachie/.gradle`, que queda fuera del área editable;
- se reejecutó fuera del sandbox con permiso y la build Android pasó;
- esto no indica fallo del proyecto, sino una restricción del entorno de ejecución de Codex.

## Limitaciones técnicas observadas

Estado actualizado: 2026-04-24 17:07 CEST.

- Las pruebas backend automáticas usan sqlite en memoria según `phpunit.xml`; son útiles para lógica de aplicación, pero no validan comportamiento específico de PostgreSQL/PostGIS.
- La extensión PostGIS se activa en migración si el driver es `pgsql`, y `/api/records` ya usa `ST_DWithin`/`ST_Distance` cuando se filtra por radio; las tablas actuales siguen usando latitud/longitud decimal y no columnas espaciales persistentes.
- El APK debug compila, pero la validación funcional completa sigue dependiendo de teléfono físico.

## Regla principal

Cada vez que aparezca una decisión real de entorno, instalación o compatibilidad, debe anotarse aquí con precisión.
