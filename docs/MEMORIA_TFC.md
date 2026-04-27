# Memoria tecnica base

Este documento es una base de redaccion para la memoria o defensa del TFC. Debe ajustarse al formato final que pida el centro.

## Resumen

Plantaria es una aplicacion colaborativa para registrar plantas geolocalizadas. El usuario puede crear reportes con foto y ubicacion, consultar registros sobre un mapa, anadir observaciones temporales y participar en una base de datos comunitaria. El sistema incorpora moderacion para validar nombres comunes y cientificos, separando el contenido provisional del contenido verificado.

## Problema

Las observaciones vegetales hechas por usuarios suelen quedar dispersas en galerias, notas personales o redes sociales. Falta una herramienta sencilla que combine:

- mapa;
- foto;
- ubicacion;
- seguimiento temporal;
- validacion por roles.

Plantaria intenta resolver ese hueco desde un MVP realista para DAM.

## Objetivos

- Construir una app Android nativa como cliente principal.
- Implementar un backend API con autenticacion y persistencia.
- Usar PostgreSQL/PostGIS como base geoespacial.
- Mostrar registros sobre mapa real.
- Permitir creacion de reportes y observaciones con foto.
- Crear un panel web para moderacion y administracion.
- Registrar eventos para analitica de uso.
- Mantener iOS, web publica completa e IA de identificacion como mejoras futuras.

## Alcance del MVP

Incluido:

- registro e inicio de sesion;
- perfil basico;
- mapa Android con MapLibre;
- pines de registros;
- ficha de registro;
- subida de fotos;
- camara y galeria;
- ubicacion del dispositivo;
- creacion de reportes;
- creacion de observaciones;
- flags;
- panel web admin/moderacion;
- dashboard analitico;
- datos demo;
- imagenes demo generadas por seeder;
- scripts de arranque e instalacion.

Fuera del primer corte:

- app iOS;
- web publica completa;
- notificaciones push;
- recuperacion avanzada de contrasena;
- IA de identificacion de plantas;
- taxonomia botanica avanzada.

## Arquitectura

Plantaria se divide en cuatro bloques:

- Android: cliente principal en Kotlin y Jetpack Compose.
- Backend: API Laravel con Sanctum y panel web Blade.
- Base de datos: PostgreSQL/PostGIS levantado con Docker Compose.
- Analitica: modulo Python auxiliar preparado para informes con pandas.

El backend centraliza autenticacion, reglas de dominio, subida de fotos, moderacion y respuestas API. Android consume la API y mantiene una URL configurable para emulador, USB o Wi-Fi.

## Modelo de dominio

Entidades principales:

- `User`: usuario con `uid` interno y `handle` publico.
- `PlantRecord`: registro geolocalizado principal.
- `Observation`: actualizacion temporal asociada a un registro.
- `ModerationFlag`: denuncia o aviso de moderacion.
- `AppEvent`: evento de uso para analitica.

El registro funciona como la ficha base. La observacion representa el historial temporal: nuevas fotos, fecha, ubicacion y estado visible de la planta.

## Roles

- `USER`: crea reportes, consulta mapa y anade observaciones.
- `MOD`: valida o rechaza registros y gestiona flags.
- `ADMIN`: gestiona usuarios y puede editar registros de forma avanzada.

## Stack

- PHP 8.3+ y Laravel 13.
- Laravel Sanctum.
- PostgreSQL/PostGIS.
- Kotlin y Jetpack Compose.
- MapLibre Native Android.
- Nominatim como proveedor de geocodificacion via proxy backend.
- Python 3 + pandas para analitica auxiliar.

## Uso de PostGIS

PostGIS se activa en migracion para PostgreSQL. El MVP guarda latitud y longitud como decimales, pero ya usa PostGIS en una consulta real: `GET /api/records` puede recibir `latitude`, `longitude` y `radius_km`, filtrar por radio con `ST_DWithin`, calcular distancia con `ST_Distance` y devolver `distance_km`.

Esto demuestra el uso real de la extension sin sobredimensionar el modelo antes de cerrar la validacion movil.

## Seguridad y validacion

- Passwords almacenadas con hash.
- Tokens Sanctum para Android.
- Middleware de cuenta activa para impedir uso de tokens de usuarios bloqueados.
- Requests Laravel para validar entradas.
- Panel web restringido a `MOD` y `ADMIN`.
- API administrativa con tests de permisos por rol.
- Edicion avanzada limitada a `ADMIN`.
- Subida de fotos protegida por autenticacion.
- Cleartext HTTP permitido solo para desarrollo local Android.

## Pruebas

Validaciones automatizadas recientes:

```text
php artisan test: 24 tests, 113 assertions
./gradlew :app:assembleDebug: BUILD SUCCESSFUL
bash -n scripts/start_mobile_stack.sh: correcto
bash -n scripts/install_debug_apk.sh: correcto
```

Tambien se ha validado manualmente contra PostgreSQL/PostGIS local el filtro por radio de `/api/records`.

## Riesgos y limitaciones

- La validacion completa en telefono fisico sigue siendo el principal bloqueo final.
- Las pruebas backend automaticas usan sqlite; no sustituyen pruebas manuales contra PostgreSQL/PostGIS.
- El estilo actual de MapLibre es de desarrollo/demo y debe sustituirse por proveedor final si se publica el producto.
- El modulo Python es auxiliar, no parte del flujo principal.

## Lineas futuras

- proveedor final de tiles vectoriales;
- columnas espaciales persistentes e indices geoespaciales mas avanzados;
- web publica de consulta;
- IA de sugerencia de especie mediante API externa;
- notificaciones;
- analitica mas avanzada con informes exportables;
- posible app iOS.
