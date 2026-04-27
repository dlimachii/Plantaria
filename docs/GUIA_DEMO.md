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
- un moderador o administrador revisa contenido desde el panel web.

## Preparacion

Desde la raiz del repo:

```bash
./scripts/start_mobile_stack.sh
```

En otro terminal:

```bash
cd android
./gradlew :app:assembleDebug
```

Para instalar en un telefono con depuracion USB:

```bash
./scripts/install_debug_apk.sh
```

Si se usa USB para conectar app y backend:

```bash
adb reverse tcp:8000 tcp:8000
```

URL de API en la app:

```text
http://127.0.0.1:8000/api/
```

Si se usa emulador:

```text
http://10.0.2.2:8000/api/
```

## Usuarios demo

Usuario Android:

```text
handle: plantaria_demo
password: PlantariaDemo1
```

Usuario admin:

```text
handle: plantaria_admin
password: PlantariaAdmin1
```

El admin puede variar si se cambian las variables `PLANTARIA_ADMIN_*` del `.env`.

## Guion recomendado

### 1. Presentar el producto

Explicacion corta:

Plantaria es una plataforma colaborativa para registrar plantas encontradas en ubicaciones reales. Cada registro nace como reporte de usuario con foto y coordenadas, puede recibir observaciones posteriores y puede ser validado por moderadores.

### 2. Login Android

- Abrir la app.
- Confirmar o editar la URL de API.
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

### 8. Panel web

Abrir:

```text
http://127.0.0.1:8000/admin
```

Mostrar:

- dashboard con analitica;
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
- Revisar URL en la pantalla de acceso.
- Para telefono por USB, repetir `adb reverse tcp:8000 tcp:8000`.
- Para Wi-Fi, usar la IP LAN del PC.

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
