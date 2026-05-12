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
- Analitica: modulo Python+pandas integrado con el panel mediante snapshots CSV/JSON.

El backend centraliza autenticacion, reglas de dominio, subida de fotos, moderacion y respuestas API. Android consume la API y mantiene una URL configurable para emulador, USB, Wi-Fi o produccion. La rama actual deja preparado tambien un despliegue VPS reproducible con dominio propio, sin depender de datos privados dentro del repositorio.

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
- Ollama local como asistente opcional del panel administrativo.

## Analitica y asistente

Laravel registra eventos en `app_events` y exporta datasets operativos con `php artisan plantaria:analytics:build`. El script `analytics/build_admin_analytics.py` procesa esos CSV con pandas y genera `storage/app/analytics/output/admin_dashboard.json`, que el panel `/admin` muestra como bloque analitico.

El panel tambien prepara `/admin/assistant`, una pagina solo para `ADMIN` que consulta un modelo local de Ollama usando el snapshot pandas como contexto. Esta parte es opcional y no sustituye las reglas de moderacion ni el backend principal.

Ademas, el panel dispone de consultas directas seguras a base de datos y de una ruta `/admin/assistant/sql` para SQL de solo lectura restringido a `ADMIN`. Esto permite explotar datos administrativos en demo o en operativa sin abrir escrituras directas ni depender obligatoriamente de la IA.

## Uso de PostGIS

PostGIS se activa en migracion para PostgreSQL. El MVP guarda latitud y longitud como decimales, pero ya usa PostGIS en una consulta real: `GET /api/records` puede recibir `latitude`, `longitude` y `radius_km`, filtrar por radio con `ST_DWithin`, calcular distancia con `ST_Distance` y devolver `distance_km`.

Esto demuestra el uso real de la extension sin sobredimensionar el modelo antes de cerrar la validacion movil.

## Seguridad y validacion

- Passwords almacenadas con hash.
- Tokens Sanctum para Android.
- Middleware de cuenta activa para impedir uso de tokens de usuarios bloqueados.
- Requests Laravel para validar entradas.
- Saneado ligero de inputs en `FormRequest`.
- CORS configurable por entorno y cerrado a origenes concretos en produccion.
- Rate limiting para login API, login admin web, geocoding, subida de fotos y asistente admin.
- Panel web restringido a `MOD` y `ADMIN`.
- API administrativa con tests de permisos por rol.
- Edicion avanzada limitada a `ADMIN`.
- Subida de fotos protegida por autenticacion.
- SQL administrativo solo de lectura con bloqueo de escrituras, comentarios, multi statement y palabras clave peligrosas.
- Cleartext HTTP permitido solo para desarrollo local Android.

Como decision de alcance, no se ha implantado todavia `row level security` real en PostgreSQL. La proteccion actual se apoya en roles de aplicacion, validaciones, throttling y restricciones del backend, lo que encaja mejor con el MVP y reduce riesgo operativo en esta fase.

## Pruebas

Validaciones automatizadas recientes:

```text
php artisan test: 43 tests, 205 assertions
./gradlew :app:assembleProdDebug: BUILD SUCCESSFUL
bash -n scripts/start_mobile_stack.sh: correcto
bash -n scripts/install_debug_apk.sh: correcto
bash -n scripts/profile_app_performance.sh: correcto
```

Tambien se ha validado contra PostgreSQL/PostGIS local el filtro por radio de `/api/records`, la generacion del snapshot Python+pandas y el perfilado rapido de endpoints criticos de la app.

En el despliegue VPS se han comprobado ademas tres evidencias operativas: la respuesta publica de la API, el preflight CORS con origen permitido y la ejecucion correcta del servicio de SQL de solo lectura. Antes del endurecimiento se genero backup del backend vivo para poder revertir el cambio sin perdida de trabajo.

## Riesgos y limitaciones

- La validacion completa en telefono fisico sigue siendo el principal bloqueo final.
- Las pruebas backend automaticas usan sqlite; no sustituyen pruebas manuales contra PostgreSQL/PostGIS.
- El mapa usa MapLibre Native Android con un estilo publico de demo/desarrollo; queda documentado como suficiente para el TFC local, no como infraestructura final de produccion.
- El modulo Python es auxiliar, no parte del flujo principal.
- La capa de seguridad actual endurece backend y panel, pero no equivale a RLS nativo en base de datos; esa mejora se deja como evolucion posterior si el proyecto crece.

## Lineas futuras

- proveedor final de tiles vectoriales;
- columnas espaciales persistentes e indices geoespaciales mas avanzados;
- web publica de consulta;
- IA de sugerencia de especie mediante API externa;
- notificaciones;
- analitica mas avanzada con informes exportables;
- posible app iOS.
