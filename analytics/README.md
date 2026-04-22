# Analytics

Este directorio recoge la parte analítica complementaria de `Plantaria`.

Objetivo:

- explotar eventos y métricas de uso de la app;
- demostrar tratamiento de datos con `Python + pandas`;
- generar CSV y gráficas reutilizables desde el panel admin o en la memoria del TFC.

Flujo previsto:

1. El backend Laravel registra eventos de uso en `app_events`.
2. Los scripts de este directorio leen PostgreSQL.
3. Se generan agregados, exportaciones y gráficas.

Uso orientativo:

```bash
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
python usage_report.py
```

Variables esperadas:

- `ANALYTICS_DB_HOST`
- `ANALYTICS_DB_PORT`
- `ANALYTICS_DB_NAME`
- `ANALYTICS_DB_USER`
- `ANALYTICS_DB_PASSWORD`

Salida prevista:

- `output/daily_active_users.csv`
- `output/hourly_activity.csv`
- `output/top_searches.csv`
- `output/*.png`
