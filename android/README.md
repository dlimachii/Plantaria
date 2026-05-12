# Plantaria Android

Cliente Android inicial de `Plantaria`.

## Estado actual

- Kotlin + Jetpack Compose.
- Navegacion inferior: `Mapa`, `Acciones`, `Usuario`.
- Login y registro contra la API Laravel.
- Token persistido con DataStore.
- Carga de registros desde `GET /api/records`.
- Mapa real con MapLibre Native Android y base OSM demo.
- Seleccion de imagen con Photo Picker.
- Captura directa con camara usando `TakePicture` y `FileProvider`.
- Subida real de imagenes contra `POST /api/uploads/photos`.
- Creacion de reportes contra `POST /api/records`.
- Actualizacion de registros con observaciones contra `POST /api/records/{id}/observations`.
- Boton para usar ubicacion actual al crear reportes u observaciones.
- Pestana `Usuario` con perfil, rol, cierre de sesion y actividad reciente propia desde `GET /api/me/activity`.

## Mapa

La pestaña `Mapa` usa MapLibre Native Android. El estilo por defecto se configura en `BuildConfig`:

```text
https://demotiles.maplibre.org/style.json
```

Ese valor vive en `PLANTARIA_MAP_STYLE_URL` dentro de `app/build.gradle.kts`, de modo que cambiar de proveedor no obliga a tocar el código Kotlin.

Para este TFC se mantiene ese estilo como base de desarrollo/demo documentada. Necesita conexion a internet y no debe presentarse como infraestructura final de produccion. Los registros de `GET /api/records` se pintan como marcadores sobre el mapa.

## API

La variante `prod` compila por defecto contra la API de produccion del VPS real:

```text
https://api.dlimachii.com/api/
```

La pantalla de acceso sigue permitiendo cambiar el servidor manualmente cuando hace falta probar backend local o un tunel temporal.

Si un movil tenia guardada una URL antigua de desarrollo (`127.0.0.1`, `10.0.2.2`, `localhost` o `0.0.0.0`), la app actual la descarta al arrancar y vuelve a la URL base configurada en la build.

### Script rapido

Desde la raiz del repo se puede levantar todo lo necesario para la prueba movil con:

```bash
./scripts/start_mobile_stack.sh
```

Ese script:

- arranca `postgis`;
- ejecuta migraciones + seed;
- asegura `storage:link`;
- deja Laravel sirviendo en `0.0.0.0:8000`.

## API local

Para probar contra Laravel local, hay que cambiar manualmente el servidor desde la pantalla de acceso y luego usar una de estas rutas:

```text
Emulador: http://10.0.2.2:8000/api/
Movil fisico por USB: http://127.0.0.1:8000/api/
```

En movil fisico se espera usar `adb reverse tcp:8000 tcp:8000`. En este entorno, ADB para el telefono fisico debe ejecutarse desde Windows PowerShell; `scripts/install_debug_apk.ps1` prepara el reverse e instala el APK, pero la URL debe guardarse en la app si se quiere usar el backend local.

### Emulador Android

La app usa:

```text
http://10.0.2.2:8000/api/
```

Arrancar Laravel:

```bash
cd ../backend
php artisan serve --host=0.0.0.0 --port=8000
```

### Movil fisico por Wi-Fi

Si se quiere probar por Wi-Fi, hay que guardar la IP LAN o el tunel publico desde la pantalla de acceso.

Como el backend se ejecuta dentro de WSL2, para un flujo Wi-Fi tambien puede hacer falta publicar el puerto desde Windows hacia WSL. Con PowerShell como administrador:

```powershell
netsh interface portproxy add v4tov4 listenaddress=0.0.0.0 listenport=8000 connectaddress=172.28.172.172 connectport=8000
New-NetFirewallRule -DisplayName "Plantaria Laravel 8000" -Direction Inbound -Action Allow -Protocol TCP -LocalPort 8000
```

Despues arrancar Laravel en WSL:

```bash
cd ../backend
php artisan serve --host=0.0.0.0 --port=8000
```

Si WSL cambia de IP tras reiniciar, repetir el portproxy con la IP nueva de WSL.

### Movil fisico por USB

Si `adb devices` detecta el telefono, se puede usar port forwarding por USB:

```bash
adb reverse tcp:8000 tcp:8000
```

La app usa esa ruta local automaticamente en dispositivo fisico.

## Compilar

En otro terminal:

```bash
cd android
./gradlew :app:assembleProdDebug
```

El APK queda en:

```text
app/build/outputs/apk/prod/debug/app-prod-debug.apk
```

## Documentación técnica Android

La rama `TFG` integra Dokka para generar documentación HTML a partir de KDoc:

```bash
../scripts/generate_technical_docs.sh
```

Salida:

```text
app/build/documentation/html/index.html
```

## Instalar en movil fisico

Primero compilar desde WSL:

```bash
cd android
./gradlew :app:assembleProdDebug
```

Despues instalar desde Windows PowerShell:

```powershell
powershell -ExecutionPolicy Bypass -File "\\wsl.localhost\TuDistro\home\TU_USUARIO\CEAC\Proyecto\scripts\install_debug_apk.ps1"
```

Comandos manuales equivalentes en PowerShell:

```powershell
$Apk = wsl wslpath -w /home/TU_USUARIO/CEAC/Proyecto/android/app/build/outputs/apk/prod/debug/app-prod-debug.apk
adb devices
adb reverse tcp:8000 tcp:8000
adb install -r $Apk
```

Si el telefono pregunta por la depuracion USB, aceptar la huella RSA.

El script Bash queda como alternativa solo si ADB detecta el movil desde WSL:

```bash
./scripts/install_debug_apk.sh
```

Para compilar e instalar variantes en paralelo (útil para demos), usar `PLANTARIA_ANDROID_FLAVOR=demoA|demoB|demoC`:

```bash
PLANTARIA_ANDROID_FLAVOR=demoA ./scripts/install_debug_apk.sh
```

## Backend: imagenes

El backend guarda imagenes en el disco `public` de Laravel.

Antes de probar fotos en una instalacion limpia conviene ejecutar:

```bash
cd ../backend
php artisan storage:link
```

## Camara

La app puede hacer una foto directa desde `Acciones`. La imagen se guarda primero en cache interna mediante `FileProvider` y despues se sube al backend usando el mismo endpoint de imagenes.

## Datos demo

Para cargar registros de prueba alrededor de Barcelona:

```bash
cd ../backend
php artisan db:seed --class=DatabaseSeeder
```

El seeder crea usuarios por rol:

```text
USER  · plantaria_user
MOD   · plantaria_mod
ADMIN · plantaria_admin
```

Tambien existe `plantaria_demo` como usuario con datos demo.

Las contraseñas de demo se configuran en `backend/.env` y no se publican en el repo.

`plantaria_user`, `plantaria_mod` y `plantaria_admin` empiezan sin reportes demo propios. Su pestana `Usuario` debe mostrar actividad vacia hasta que esa cuenta cree reportes, observaciones, denuncias o acciones de moderacion/admin.

## Pendiente

- Si Plantaria se publica fuera del TFC, sustituir el estilo demo por un proveedor final de tiles compatible con MapLibre o por hosting propio.
