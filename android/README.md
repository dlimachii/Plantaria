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

## Mapa

La pestaña `Mapa` usa MapLibre Native Android. El estilo por defecto se configura en `BuildConfig`:

```text
https://demotiles.maplibre.org/style.json
```

Ese valor vive en `PLANTARIA_MAP_STYLE_URL` dentro de `app/build.gradle.kts`, de modo que cambiar de proveedor no obliga a tocar el código Kotlin.

El estilo demo necesita conexion a internet y sirve para desarrollo/demo. No debe considerarse la solucion final de produccion. Los registros de `GET /api/records` se pintan como marcadores sobre el mapa.

## API local

La app usa por defecto:

```text
http://10.0.2.2:8000/api/
```

Esa URL esta pensada para el emulador Android, donde `10.0.2.2` apunta al host que ejecuta Laravel.

La pantalla de acceso permite cambiar la URL de API y la guarda en DataStore.

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

### Emulador Android

Usar:

```text
http://10.0.2.2:8000/api/
```

Arrancar Laravel:

```bash
cd ../backend
php artisan serve --host=0.0.0.0 --port=8000
```

### Movil fisico por Wi-Fi

En movil fisico no sirve `10.0.2.2`. La app debe apuntar a la IP LAN del PC:

```text
http://IP_DEL_PC:8000/api/
```

En este entorno se ha observado como candidata:

```text
http://10.4.20.61:8000/api/
```

Como el backend se ejecuta dentro de WSL2, puede hacer falta publicar el puerto desde Windows hacia WSL. Con PowerShell como administrador:

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

En la app usar:

```text
http://127.0.0.1:8000/api/
```

## Compilar

En otro terminal:

```bash
cd android
./gradlew :app:assembleDebug
```

El APK queda en:

```text
app/build/outputs/apk/debug/app-debug.apk
```

## Instalar en movil fisico

Con depuracion USB activada:

```bash
adb devices
adb install -r app/build/outputs/apk/debug/app-debug.apk
```

Si el telefono pregunta por la depuracion USB, aceptar la huella RSA.

Tambien se puede compilar e instalar con:

```bash
./scripts/install_debug_apk.sh
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

El seeder crea tambien un usuario demo:

```text
handle: plantaria_demo
password: PlantariaDemo1
```

## Pendiente

- Elegir y fijar el proveedor/estrategia final de tiles para produccion.
