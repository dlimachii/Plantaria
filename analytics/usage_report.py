from __future__ import annotations

import os
from pathlib import Path

import matplotlib.pyplot as plt
import pandas as pd
from sqlalchemy import create_engine, text


BASE_DIR = Path(__file__).resolve().parent
OUTPUT_DIR = BASE_DIR / "output"


def db_url() -> str:
    host = os.getenv("ANALYTICS_DB_HOST", "127.0.0.1")
    port = os.getenv("ANALYTICS_DB_PORT", "5432")
    name = os.getenv("ANALYTICS_DB_NAME", "plantaria")
    user = os.getenv("ANALYTICS_DB_USER", "plantaria")
    password = os.getenv("ANALYTICS_DB_PASSWORD", "plantaria")

    return f"postgresql+psycopg://{user}:{password}@{host}:{port}/{name}"


def export_dataframe(dataframe: pd.DataFrame, name: str) -> None:
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    dataframe.to_csv(OUTPUT_DIR / f"{name}.csv", index=False)


def export_line_chart(dataframe: pd.DataFrame, x_col: str, y_col: str, title: str, filename: str) -> None:
    fig, ax = plt.subplots(figsize=(10, 4.5))
    ax.plot(dataframe[x_col], dataframe[y_col], marker="o")
    ax.set_title(title)
    ax.set_xlabel(x_col)
    ax.set_ylabel(y_col)
    ax.grid(alpha=0.25)
    fig.tight_layout()
    fig.savefig(OUTPUT_DIR / filename)
    plt.close(fig)


def export_bar_chart(dataframe: pd.DataFrame, x_col: str, y_col: str, title: str, filename: str) -> None:
    fig, ax = plt.subplots(figsize=(10, 4.5))
    ax.bar(dataframe[x_col], dataframe[y_col])
    ax.set_title(title)
    ax.set_xlabel(x_col)
    ax.set_ylabel(y_col)
    ax.grid(axis="y", alpha=0.25)
    fig.tight_layout()
    fig.savefig(OUTPUT_DIR / filename)
    plt.close(fig)


def main() -> None:
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    engine = create_engine(db_url())

    daily_active_users = pd.read_sql_query(
        text(
            """
            SELECT
                DATE(occurred_at) AS day,
                COUNT(DISTINCT user_id) AS active_users
            FROM app_events
            WHERE user_id IS NOT NULL
            GROUP BY DATE(occurred_at)
            ORDER BY day
            """
        ),
        engine,
    )

    hourly_activity = pd.read_sql_query(
        text(
            """
            SELECT
                TO_CHAR(occurred_at, 'HH24') AS hour,
                COUNT(*) AS total_events
            FROM app_events
            GROUP BY hour
            ORDER BY hour
            """
        ),
        engine,
    )

    top_searches = pd.read_sql_query(
        text(
            """
            SELECT
                search_query,
                search_type,
                COUNT(*) AS total
            FROM app_events
            WHERE event_type = 'map_search'
              AND search_query IS NOT NULL
            GROUP BY search_query, search_type
            ORDER BY total DESC
            LIMIT 20
            """
        ),
        engine,
    )

    export_dataframe(daily_active_users, "daily_active_users")
    export_dataframe(hourly_activity, "hourly_activity")
    export_dataframe(top_searches, "top_searches")

    if not daily_active_users.empty:
        export_line_chart(
            daily_active_users,
            "day",
            "active_users",
            "Usuarios activos por dia",
            "daily_active_users.png",
        )

    if not hourly_activity.empty:
        export_bar_chart(
            hourly_activity,
            "hour",
            "total_events",
            "Actividad por hora",
            "hourly_activity.png",
        )

    if not top_searches.empty:
        export_bar_chart(
            top_searches.head(10),
            "search_query",
            "total",
            "Top busquedas",
            "top_searches.png",
        )

    print(f"Analitica exportada en: {OUTPUT_DIR}")


if __name__ == "__main__":
    main()
