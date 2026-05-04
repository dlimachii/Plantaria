# Analytics

Este directorio recoge la parte analítica complementaria de `Plantaria`.

Objetivo:

- explotar eventos y métricas de uso de la app;
- demostrar tratamiento de datos con `Python + pandas`;
- generar CSV, JSON y gráficas reutilizables desde el panel admin o en la memoria del TFC.

## Flujo integrado con el panel web

1. El backend Laravel registra eventos de uso en `app_events`.
2. El comando Artisan exporta datasets normalizados a `backend/storage/app/analytics/input`.
3. `build_admin_analytics.py` lee esos CSV con `pandas`.
4. El script genera `backend/storage/app/analytics/output/admin_dashboard.json`.
5. El dashboard `/admin` lee ese JSON y muestra los KPIs calculados con pandas.

Uso desde la raiz del backend:

```bash
cd backend
php artisan plantaria:analytics:build
```

Para validar solo la exportacion CSV sin ejecutar pandas:

```bash
php artisan plantaria:analytics:build --skip-python
```

Si falta pandas:

```bash
python3 -m venv .venv
source .venv/bin/activate
pip install -r ../analytics/requirements.txt
PLANTARIA_ANALYTICS_PYTHON=../analytics/.venv/bin/python php artisan plantaria:analytics:build
```

Salida integrada:

- `backend/storage/app/analytics/input/users.csv`
- `backend/storage/app/analytics/input/plant_records.csv`
- `backend/storage/app/analytics/input/observations.csv`
- `backend/storage/app/analytics/input/moderation_flags.csv`
- `backend/storage/app/analytics/input/app_events.csv`
- `backend/storage/app/analytics/output/admin_dashboard.json`

## Script historico directo a PostgreSQL

`usage_report.py` se mantiene como script auxiliar independiente que lee PostgreSQL y genera CSV/PNG en `analytics/output`.

Variables esperadas por `usage_report.py`:

- `ANALYTICS_DB_HOST`
- `ANALYTICS_DB_PORT`
- `ANALYTICS_DB_NAME`
- `ANALYTICS_DB_USER`
- `ANALYTICS_DB_PASSWORD`

Salida prevista del script historico:

- `output/daily_active_users.csv`
- `output/hourly_activity.csv`
- `output/top_searches.csv`
- `output/*.png`

## Ollama

El panel `/admin/assistant` usa consultas directas seguras a BBDD para preguntas administrativas conocidas. El JSON calculado con pandas aporta contexto para consultas locales abiertas con Ollama, pero no es obligatorio para esas consultas directas.

Configuracion en `.env` del backend:

```text
OLLAMA_BASE_URL=http://127.0.0.1:11434
OLLAMA_MODEL=llama3.2:1b
```

Preparacion local orientativa:

```bash
ollama pull llama3.2:1b
ollama serve
```
