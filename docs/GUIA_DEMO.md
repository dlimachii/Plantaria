# Guia de demo de Plantaria

Esta guia sirve para ejecutar una demostracion completa del MVP con backend local, PostgreSQL/PostGIS y app Android.

## Objetivo de la demo

Mostrar que Plantaria funciona como producto de punta a punta:

- un usuario inicia sesion en Android;
- consulta registros reales sobre un mapa;
- busca plantas y zonas;
- abre una ficha con fotos y observaciones;
- crea un reporte con foto y ubicacion;
- añade una observacion temporal a un registro;
- revisa que la pestana `Usuario` muestra actividad propia, no todos los registros de la app;
- un moderador o administrador revisa contenido desde el panel web.

## Preparacion

Desde la raiz del repo:

```bash
./scripts/start_mobile_stack.sh
```

En otro terminal:

```bash
cd android
./gradlew :app:assembleProdDebug
```

### Opcional: demo multi-APK (A/B/C en el mismo movil)

Esto permite tener 3 apps instaladas a la vez (sesiones separadas) y evitar desloguear/reloguear durante la presentacion.

1) Compilar las 3 APKs:

```bash
./scripts/build_demo_apks.sh
```

2) Instalar en el movil (recomendado desde Windows PowerShell):

```powershell
powershell -ExecutionPolicy Bypass -File "\\wsl.localhost\TuDistro\home\TU_USUARIO\CEAC\Proyecto\scripts\install_demo_apks.ps1"
```

Si tu distro WSL no se llama `TuDistro`, cambia el nombre en esa ruta.

Quedaran instaladas como:

- `Plantaria Demo A`
- `Plantaria Demo B`
- `Plantaria Demo C`

Para instalar en un telefono con depuracion USB, usar Windows PowerShell. En este entorno el movil se detecta de forma fiable desde Windows, no desde WSL:

```powershell
powershell -ExecutionPolicy Bypass -File "\\wsl.localhost\TuDistro\home\TU_USUARIO\CEAC\Proyecto\scripts\install_debug_apk.ps1"
```

Comandos manuales equivalentes:

```powershell
$Apk = wsl wslpath -w /home/TU_USUARIO/CEAC/Proyecto/android/app/build/outputs/apk/prod/debug/app-prod-debug.apk
adb devices
adb reverse tcp:8000 tcp:8000
adb install -r $Apk
```

El script Bash solo es alternativa si ADB ve el telefono desde WSL:

```bash
./scripts/install_debug_apk.sh
```

Si se usa USB para conectar app y backend:

```bash
adb reverse tcp:8000 tcp:8000
```

La app permite configurar el servidor (API) en la pantalla de acceso. En la build de demo puede venir ya configurada a `https://api.dlimachii.com/api/`. Si se trabaja en local, usa `adb reverse` o un tunel HTTPS y guarda la URL desde login.

### Demo sin hosting (Cloudflare Quick Tunnel)

Si se necesita que varios dispositivos fuera de tu red accedan a la API durante la demo, se puede exponer el backend local con un tunel temporal (sin dominio).

Opcion recomendada (1 comando):

```bash
bash ./scripts/start_demo_tunnel.sh
```

Alternativa manual:

1. Arranca backend + BBDD como siempre (por ejemplo `./scripts/start_mobile_stack.sh`).
2. Crea el tunel:

```bash
cloudflared tunnel --url http://localhost:8000
```

`cloudflared` imprimira una URL `https://....trycloudflare.com`.

3. En la app Android, en login, pega esa URL en `Servidor (API)` y pulsa `Guardar servidor`.

#### Opcional: bootstrap remoto

El cliente mantiene soporte para configurar el servidor manualmente desde login. Si mas adelante quieres un bootstrap remoto por JSON, conviene volver a habilitar `PLANTARIA_BOOTSTRAP_CONFIG_URL` en la build y servir un documento publico con `api_base_url`.

## Usuarios demo

Cuentas de prueba por rol:

```text
USER  · plantaria_user
MOD   · plantaria_mod
ADMIN · plantaria_admin
```

Usuario con datos demo:

```text
plantaria_demo
```

Las contraseñas de demo se configuran en `backend/.env` (no se publican en el repo).

Las cuentas `plantaria_user`, `plantaria_mod` y `plantaria_admin` empiezan sin actividad propia de demo. Son utiles para comprobar que el perfil no lista registros globales hasta que la cuenta haga acciones reales.

El admin puede variar si se cambian las variables `PLANTARIA_ADMIN_*` del `.env`.

## Guion recomendado

### 1. Presentar el producto

Explicacion corta:

Plantaria es una plataforma colaborativa para registrar plantas encontradas en ubicaciones reales. Cada registro nace como reporte de usuario con foto y coordenadas, puede recibir observaciones posteriores y puede ser validado por moderadores.

### 2. Login Android

- Abrir la app.
- Entrar con `plantaria_demo`.
- Comprobar que la app carga sesion y registros.

### 3. Mapa

- Entrar en `Mapa`.
- Mostrar los pines demo alrededor de Barcelona.
- Tocar un pin para abrir preview.
- Abrir la ficha completa desde la preview.
- Mostrar foto, estado de verificacion, autor y observaciones.

### 4. Busqueda

- Buscar una planta por texto, por ejemplo `Lavanda`.
- Usar la busqueda de zona con `Barcelona` o coordenadas `41.3851, 2.1734`.
- Explicar que la busqueda de zonas pasa por el proxy backend de Nominatim y que la busqueda de registros consulta la API propia.

### 5. Ubicacion

- Pulsar `Mi ubicacion` en el mapa si el dispositivo tiene permisos.
- En `Acciones`, usar el boton de ubicacion actual para rellenar coordenadas.
- Si GPS no responde, introducir coordenadas manuales como fallback.

### 6. Crear reporte

- Ir a `Acciones`.
- Elegir o hacer una foto.
- Rellenar nombre provisional.
- Usar ubicacion actual o coordenadas manuales.
- Crear el reporte.
- Volver al mapa y comprobar que aparece.

### 7. Anadir observacion

- Abrir una ficha.
- Usar `Anadir observacion`.
- Confirmar que el ID queda prellenado en `Acciones`.
- Adjuntar foto, nota y coordenadas.
- Guardar observacion.
- Volver a la ficha y comprobar el historial.

### 8. Actividad de usuario

- Entrar en `Usuario`.
- Confirmar que aparecen las ultimas acciones de la cuenta actual.
- Probar `plantaria_user` recien seedado: debe aparecer sin actividad reciente.
- Crear un reporte o una observacion y volver a `Usuario`: debe aparecer solo esa accion propia.

### 9. Panel web

Abrir:

```text
https://api.dlimachii.com/admin
```

Mostrar:

- dashboard con analitica;
- bloque `Analitica Python + pandas` tras ejecutar `php artisan plantaria:analytics:build`;
- asistente `/admin/assistant` con Ollama si el servicio local esta levantado;
- cola de moderacion;
- detalle de registro;
- verificar o rechazar;
- flags;
- gestion de usuarios si se entra como `ADMIN`.

## Punto tecnico destacable

`GET /api/records` puede filtrar por radio:

```text
/api/records?latitude=41.3851&longitude=2.1734&radius_km=8&limit=10
```

En PostgreSQL usa PostGIS (`ST_DWithin` y `ST_Distance`) y devuelve `distance_km`.

Validacion local realizada el 2026-04-24 17:26 CEST:

- PostGIS levantado en Docker;
- migraciones al dia;
- seeders cargados;
- endpoint por radio probado contra Laravel local;
- respuesta con registros demo y distancias.

## Si algo falla en demo

### La app no conecta

- Revisar que Laravel esta en marcha.
- Para telefono por USB, repetir `adb reverse tcp:8000 tcp:8000`.
- Para Wi-Fi, preparar una build con la URL LAN porque el campo tecnico ya no se muestra en login.

### Las fotos no cargan

- Ejecutar `php artisan storage:link`.
- Revisar que la URL de API guardada en Android no apunta a `localhost` si se usa Wi-Fi.

### La subida de foto falla

- Usar `scripts/start_mobile_stack.sh`, que arranca PHP con limites de subida ampliados.
- Probar una foto mas ligera.
- Confirmar permisos de camara/galeria.

### GPS no responde

- Conceder permisos de ubicacion.
- Activar ubicacion del dispositivo.
- Usar coordenadas manuales para continuar la demo.
