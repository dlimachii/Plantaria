# Plantaria

`Plantaria` es un TFC de DAM orientado a una plataforma colaborativa de plantas geolocalizadas: un usuario registra una planta con foto y ubicacion, otros usuarios pueden añadir observaciones temporales, y moderadores o administradores validan la informacion botanica.

## Estado del MVP

El proyecto ya contiene un flujo real de punta a punta:

- backend Laravel con Sanctum, API, panel web de moderacion/admin y subida de fotos;
- PostgreSQL/PostGIS local con Docker Compose;
- cliente Android Kotlin/Jetpack Compose con MapLibre, login, mapa, reportes, observaciones, camara, galeria y GPS;
- modulo auxiliar `analytics/` en Python+pandas conectado al panel admin;
- scripts de apoyo para prueba movil.

La prioridad actual es estabilizar, validar en telefono fisico y documentar la entrega. iOS, web publica completa e IA de identificacion vegetal quedan como fases posteriores.

## Estructura

```text
backend/    API Laravel, panel web y tests feature
android/    app Android Kotlin + Jetpack Compose
analytics/  scripts Python para analitica auxiliar
scripts/    utilidades de arranque e instalacion de APK
Contexto/   memoria tecnica viva entre sesiones
compose.yaml PostgreSQL/PostGIS local
```

## Arranque rapido

Desde la raiz del repo:

```bash
./scripts/start_mobile_stack.sh
```

Ese comando levanta PostGIS, migra y rellena datos demo, prepara storage e inicia Laravel en:

```text
http://0.0.0.0:8000
```

URLs de API para Android:

- emulador: `http://10.0.2.2:8000/api/`
- movil por USB con `adb reverse`: `http://127.0.0.1:8000/api/`
- movil por Wi-Fi: `http://IP_DEL_PC:8000/api/`

## Backend

Arranque manual:

```bash
docker compose up -d postgis
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve --host=0.0.0.0 --port=8000
```

Panel admin:

```text
http://127.0.0.1:8000/admin
```

Mas detalle en `backend/README.md`.

Analitica pandas del panel:

```bash
cd backend
php artisan plantaria:analytics:build
```

Ese comando exporta datos a `storage/app/analytics/input`, calcula `storage/app/analytics/output/admin_dashboard.json` con pandas y lo enseña en `/admin`. El asistente local de `/admin/assistant` usa Ollama si `OLLAMA_BASE_URL` y `OLLAMA_MODEL` apuntan a un modelo disponible.

## Android

Compilar APK debug:

```bash
cd android
./gradlew :app:assembleDebug
```

Instalar en movil fisico:

Desde Windows PowerShell, que es donde tu telefono queda visible por ADB:

```powershell
powershell -ExecutionPolicy Bypass -File "\\wsl.localhost\Ubuntu\home\aviddrianimachie\CEAC\Proyecto\scripts\install_debug_apk.ps1"
```

Comandos manuales equivalentes en PowerShell:

```powershell
$Apk = wsl wslpath -w /home/aviddrianimachie/CEAC/Proyecto/android/app/build/outputs/apk/debug/app-debug.apk
adb devices
adb reverse tcp:8000 tcp:8000
adb install -r $Apk
```

El script Bash queda solo como alternativa si ADB detecta el dispositivo desde WSL:

```bash
../scripts/install_debug_apk.sh
```

APK generado:

```text
android/app/build/outputs/apk/debug/app-debug.apk
```

Mas detalle en `android/README.md`.

## Datos demo

El seeder deja cuentas de prueba por rol:

```text
USER  · plantaria_user  / PlantariaUser1
MOD   · plantaria_mod   / PlantariaMod1
ADMIN · plantaria_admin / PlantariaAdmin1
```

Tambien existe `plantaria_demo / PlantariaDemo1` como usuario de demo con registros cargados alrededor de Barcelona.

La cuenta admin principal se puede configurar con:

```text
PLANTARIA_ADMIN_HANDLE
PLANTARIA_ADMIN_EMAIL
PLANTARIA_ADMIN_PASSWORD
```

## Documentacion de entrega

- `docs/GUIA_DEMO.md`: guion para ensenar el MVP.
- `docs/CHECKLIST_VALIDACION_MOVIL.md`: lista de prueba fisica del APK.
- `docs/MEMORIA_TFC.md`: base tecnica para memoria o defensa.
- `docs/API.md`: referencia practica de endpoints.
- `docs/BACKUP_ONEDRIVE.md`: empaquetado limpio del proyecto para OneDrive.
- `DocumentoTFG/TFG DAM_DAW.docx`: plantilla original del documento TFG.
- `DocumentoTFG/Plantaria_TFG_DAM.docx`: documento generado y versionado como referencia para la estructura y contexto del TFG.
- `scripts/generate_tfg_docx.py`: generador reproducible del DOCX de Plantaria.

Regenerar el documento TFG:

```bash
python3 scripts/generate_tfg_docx.py
```

## Validacion

Backend:

```bash
cd backend
php artisan test
```

Android:

```bash
cd android
./gradlew :app:assembleDebug
```

Scripts:

```bash
bash -n scripts/start_mobile_stack.sh
bash -n scripts/install_debug_apk.sh
```

Validacion integral:

```bash
./scripts/validate_project.sh
```

Perfilado rapido para optimizacion:

```bash
./scripts/profile_app_performance.sh
```

Ese script mide tiempos de endpoints usados por la app, revisa el tamano del APK debug y, si hay un movil por ADB con la app abierta, muestra un snapshot basico de memoria/render. Variables utiles:

- `PLANTARIA_PROFILE_RUNS=10` cambia el numero de iteraciones.
- `PLANTARIA_PROFILE_BASE_URL=http://127.0.0.1:8000` apunta a una API ya arrancada.
- `PLANTARIA_PROFILE_PORT=8020` cambia el puerto temporal si el script arranca Laravel.

Variables utiles:

- `SKIP_ANDROID_BUILD=1` salta la compilacion Android.
- `SKIP_POSTGIS_SMOKE=1` salta la prueba HTTP contra PostgreSQL/PostGIS.
- `PLANTARIA_VALIDATE_PORT=8010` cambia el puerto temporal de validacion.

Estado reciente:

```text
php artisan test: 38 tests, 176 assertions
./gradlew :app:assembleDebug: BUILD SUCCESSFUL
php artisan plantaria:analytics:build: correcto
./scripts/profile_app_performance.sh: correcto
```

La validacion integral tambien comprueba que los tokens de usuarios no activos quedan bloqueados, que las rutas admin API respetan roles, que la actividad propia de usuario no mezcla registros globales y que la exportacion de analitica genera datasets.

## Pendiente real antes de entrega

- Revalidar en telefono fisico el APK actual.
- Probar login, mapa, busqueda, GPS, camara, galeria, subida de foto, creacion de reporte y observacion.
- Mantener el estilo actual de mapa como demo/desarrollo documentado; si el producto se publicase, sustituirlo por proveedor final de tiles o hosting propio.
