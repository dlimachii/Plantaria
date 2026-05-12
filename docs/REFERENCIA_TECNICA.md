# Referencia técnica

Esta guia resume la arquitectura real de Plantaria y sirve como punto de entrada tecnico
para entender el proyecto sin depender del contexto interno de desarrollo.

## Visión general

Plantaria se divide en cuatro bloques:

- `android/`: cliente Android nativo en Kotlin + Jetpack Compose.
- `backend/`: API Laravel 13, panel admin y tests.
- `analytics/`: scripts Python para KPIs y snapshot JSON.
- `deploy/vps/`: ejemplo reproducible de despliegue con Docker Compose y Caddy.

## Backend

Responsabilidades principales:

- autenticación y tokens con Laravel Sanctum;
- persistencia del dominio (`users`, `plant_records`, `observations`, `moderation_flags`, `app_events`);
- subida de fotos;
- geocodificación por proxy backend;
- panel web para moderación y administración;
- exportación analítica para `analytics/`.

Puntos técnicos relevantes:

- `backend/routes/api.php`: contrato HTTP consumido por Android.
- `backend/routes/web.php`: panel admin.
- `backend/app/Http/Controllers/Api/PlantRecordController.php`: listados, filtros y detalle de registros.
- `backend/app/Services/AdminReadOnlySqlQuery.php`: SQL administrativo de solo lectura.
- `backend/app/Providers/AppServiceProvider.php`: rate limiting por ruta.

## Base de datos y geoespacial

- desarrollo local con PostgreSQL/PostGIS mediante `compose.yaml`;
- migración que habilita PostGIS solo cuando el driver real es `pgsql`;
- filtro geográfico por radio en `GET /api/records` con `ST_DWithin` y `ST_Distance`;
- fallback para tests sobre SQLite.

## Android

Responsabilidades principales:

- login/registro y mantenimiento de sesión;
- configuración dinámica del servidor backend;
- mapa con registros, filtros y detalle;
- creación de reportes y observaciones con foto;
- actividad y perfil de usuario.

Puntos técnicos relevantes:

- `android/app/src/main/java/com/plantaria/app/data/api/PlantariaApiClient.kt`: cliente HTTP.
- `android/app/src/main/java/com/plantaria/app/data/session/SessionStore.kt`: DataStore y sesión.
- `android/app/src/main/java/com/plantaria/app/ui/state/PlantariaViewModel.kt`: estado y casos de uso de UI.
- `android/app/src/main/java/com/plantaria/app/ui/screens/MapScreen.kt`: mapa, filtros y detalle.
- `android/app/src/main/java/com/plantaria/app/ui/screens/ActionsScreen.kt`: creación de reportes y observaciones.

## Seguridad aplicada

- validación Laravel con `FormRequest`;
- saneado ligero de inputs en backend;
- `CORS` configurable por entorno;
- rate limiting para login, geocoding, uploads y panel sensible;
- SQL administrativo solo lectura;
- `OLLAMA_ENABLED=false` soportado en producción sin romper consultas directas.

## Analítica

Flujo:

1. Laravel exporta CSV desde `php artisan plantaria:analytics:build`.
2. `analytics/build_admin_analytics.py` procesa los datos con `pandas`.
3. El resultado se persiste como `admin_dashboard.json`.
4. El panel admin lo consume como snapshot operacional.

## Despliegue

Opciones previstas:

- local: `compose.yaml` + `scripts/start_mobile_stack.sh`;
- VPS: `deploy/vps/docker-compose.yml` con `backend/Dockerfile` y `deploy/vps/Caddyfile`.

Documentación específica:

- `backend/README.md`
- `android/README.md`
- `docs/API.md`
- `docs/DEPLOY_VPS.md`
