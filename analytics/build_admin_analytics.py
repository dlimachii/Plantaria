from __future__ import annotations

import argparse
import json
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

import pandas as pd


def read_csv(input_dir: Path, name: str) -> pd.DataFrame:
    path = input_dir / name
    if not path.exists():
        return pd.DataFrame()

    return pd.read_csv(path)


def parse_datetime(dataframe: pd.DataFrame, column: str) -> pd.DataFrame:
    if column in dataframe.columns:
        dataframe[column] = pd.to_datetime(dataframe[column], errors="coerce", utc=True)

    return dataframe


def int_value(value: Any) -> int:
    if pd.isna(value):
        return 0

    return int(value)


def float_value(value: Any) -> float:
    if pd.isna(value):
        return 0.0

    return float(value)


def records_to_dicts(dataframe: pd.DataFrame) -> list[dict[str, Any]]:
    clean = dataframe.where(pd.notna(dataframe), None)
    return clean.to_dict(orient="records")


def build_daily_activity(events: pd.DataFrame, generated_at: pd.Timestamp) -> list[dict[str, Any]]:
    start = (generated_at - pd.Timedelta(days=13)).normalize()
    days = pd.date_range(start=start, periods=14, freq="D", tz="UTC")

    if events.empty or "occurred_at" not in events.columns:
        grouped = pd.DataFrame(columns=["day", "total_events", "active_users"])
    else:
        recent = events[events["occurred_at"] >= start].copy()
        recent["day"] = recent["occurred_at"].dt.normalize()
        grouped = (
            recent.groupby("day", dropna=False)
            .agg(total_events=("id", "count"), active_users=("user_id", pd.Series.nunique))
            .reset_index()
        )

    calendar = pd.DataFrame({"day": days})
    daily = calendar.merge(grouped, how="left", on="day").fillna({"total_events": 0, "active_users": 0})
    daily["label"] = daily["day"].dt.strftime("%d/%m")

    return [
        {
            "day": row.day.date().isoformat(),
            "label": row.label,
            "total_events": int_value(row.total_events),
            "active_users": int_value(row.active_users),
        }
        for row in daily.itertuples()
    ]


def build_top_searches(events: pd.DataFrame) -> list[dict[str, Any]]:
    if events.empty or "event_type" not in events.columns:
        return []

    searches = events[
        (events["event_type"] == "map_search")
        & events["search_query"].notna()
        & (events["search_query"].astype(str).str.len() > 0)
    ].copy()

    if searches.empty:
        return []

    grouped = (
        searches.groupby(["search_query", "search_type"], dropna=False)
        .size()
        .reset_index(name="total")
        .sort_values("total", ascending=False)
        .head(8)
    )

    return records_to_dicts(grouped)


def build_top_creators(users: pd.DataFrame, records: pd.DataFrame, observations: pd.DataFrame) -> list[dict[str, Any]]:
    if users.empty:
        return []

    record_counts = pd.DataFrame(columns=["id", "records_count"])
    if not records.empty and "created_by_user_id" in records.columns:
        record_counts = records.groupby("created_by_user_id").size().reset_index(name="records_count")
        record_counts = record_counts.rename(columns={"created_by_user_id": "id"})

    observation_counts = pd.DataFrame(columns=["id", "observations_count"])
    if not observations.empty and "author_user_id" in observations.columns:
        updates = observations
        if "source_type" in observations.columns:
            updates = observations[observations["source_type"] == "update"]
        observation_counts = updates.groupby("author_user_id").size().reset_index(name="observations_count")
        observation_counts = observation_counts.rename(columns={"author_user_id": "id"})

    creators = (
        users[["id", "handle", "display_name", "role"]]
        .merge(record_counts, how="left", on="id")
        .merge(observation_counts, how="left", on="id")
        .fillna({"records_count": 0, "observations_count": 0})
    )
    creators["contributions"] = creators["records_count"] + creators["observations_count"]
    creators = creators.sort_values(["contributions", "records_count"], ascending=False).head(8)

    return [
        {
            "handle": row.handle,
            "display_name": row.display_name,
            "role": row.role,
            "records_count": int_value(row.records_count),
            "observations_count": int_value(row.observations_count),
            "contributions": int_value(row.contributions),
        }
        for row in creators.itertuples()
        if int_value(row.contributions) > 0
    ]


def build_activity_by_role(events: pd.DataFrame) -> list[dict[str, Any]]:
    if events.empty or "role_snapshot" not in events.columns:
        return []

    role_events = events.copy()
    role_events["role_snapshot"] = role_events["role_snapshot"].fillna("sin_usuario")
    grouped = (
        role_events.groupby("role_snapshot", dropna=False)
        .agg(total_events=("id", "count"), active_users=("user_id", pd.Series.nunique))
        .reset_index()
        .sort_values("total_events", ascending=False)
    )

    return [
        {
            "role": row.role_snapshot,
            "total_events": int_value(row.total_events),
            "active_users": int_value(row.active_users),
        }
        for row in grouped.itertuples()
    ]


def build_risk_signals(
    pending_records: int,
    open_flags: int,
    verification_rate: float,
    active_users_7d: int,
    events_7d: int,
) -> list[str]:
    signals: list[str] = []

    if pending_records > 0:
        signals.append(f"{pending_records} registros siguen pendientes de revision.")
    else:
        signals.append("No hay registros pendientes de revision.")

    if open_flags > 0:
        signals.append(f"{open_flags} flags estan abiertos o en revision.")

    if verification_rate < 60:
        signals.append("La cobertura de verificacion esta por debajo del 60%.")

    if active_users_7d == 0 or events_7d == 0:
        signals.append("No hay actividad reciente suficiente en los ultimos 7 dias.")

    return signals[:5]


def build_report(input_dir: Path) -> dict[str, Any]:
    users = read_csv(input_dir, "users.csv")
    records = read_csv(input_dir, "plant_records.csv")
    observations = read_csv(input_dir, "observations.csv")
    flags = read_csv(input_dir, "moderation_flags.csv")
    events = read_csv(input_dir, "app_events.csv")

    for dataframe, columns in (
        (users, ["created_at", "last_login_at", "deleted_at"]),
        (records, ["created_at", "verified_at", "deleted_at"]),
        (observations, ["observed_at", "created_at"]),
        (flags, ["created_at", "resolved_at"]),
        (events, ["occurred_at", "created_at"]),
    ):
        for column in columns:
            parse_datetime(dataframe, column)

    date_candidates = []
    for dataframe, column in (
        (events, "occurred_at"),
        (records, "created_at"),
        (observations, "observed_at"),
        (users, "created_at"),
    ):
        if not dataframe.empty and column in dataframe.columns:
            max_date = dataframe[column].max()
            if pd.notna(max_date):
                date_candidates.append(max_date)

    generated_at = max(date_candidates) if date_candidates else pd.Timestamp.now(tz="UTC")
    last_7_days = generated_at - pd.Timedelta(days=7)

    recent_events = events[events["occurred_at"] >= last_7_days] if not events.empty else events
    recent_records = records[records["created_at"] >= last_7_days] if not records.empty else records
    recent_observations = observations[observations["observed_at"] >= last_7_days] if not observations.empty else observations

    total_records = len(records)
    pending_records = int((records["verification_status"] == "pending").sum()) if not records.empty else 0
    verified_records = int((records["verification_status"] == "verified").sum()) if not records.empty else 0
    rejected_records = int((records["verification_status"] == "rejected").sum()) if not records.empty else 0
    open_flags = int(flags["status"].isin(["open", "reviewing"]).sum()) if not flags.empty else 0
    active_users_7d = int(recent_events["user_id"].dropna().nunique()) if not recent_events.empty else 0
    events_7d = len(recent_events)
    verification_rate = round(((verified_records + rejected_records) / total_records) * 100, 1) if total_records else 0.0

    review_hours = None
    if not records.empty and {"created_at", "verified_at"}.issubset(records.columns):
        reviewed = records[records["verified_at"].notna() & records["created_at"].notna()].copy()
        if not reviewed.empty:
            review_hours = round(((reviewed["verified_at"] - reviewed["created_at"]).dt.total_seconds() / 3600).mean(), 2)

    status_counts = (
        records.groupby("verification_status").size().reset_index(name="total").sort_values("total", ascending=False)
        if not records.empty
        else pd.DataFrame(columns=["verification_status", "total"])
    )

    condition_counts = (
        records.groupby("plant_condition").size().reset_index(name="total").sort_values("total", ascending=False)
        if not records.empty
        else pd.DataFrame(columns=["plant_condition", "total"])
    )

    kpis = {
        "events_7d": events_7d,
        "active_users_7d": active_users_7d,
        "reports_7d": len(recent_records),
        "observations_7d": len(recent_observations),
        "pending_records": pending_records,
        "verified_records": verified_records,
        "rejected_records": rejected_records,
        "open_flags": open_flags,
        "verification_rate": verification_rate,
        "avg_review_hours": review_hours,
    }

    report = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "analysis_reference_at": generated_at.isoformat(),
        "source_counts": {
            "users": len(users),
            "plant_records": len(records),
            "observations": len(observations),
            "moderation_flags": len(flags),
            "app_events": len(events),
        },
        "kpis": kpis,
        "daily_activity": build_daily_activity(events, generated_at),
        "activity_by_role": build_activity_by_role(events),
        "top_searches": build_top_searches(events),
        "top_creators": build_top_creators(users, records, observations),
        "record_status": records_to_dicts(status_counts),
        "plant_conditions": records_to_dicts(condition_counts),
        "risk_signals": build_risk_signals(
            pending_records=pending_records,
            open_flags=open_flags,
            verification_rate=verification_rate,
            active_users_7d=active_users_7d,
            events_7d=events_7d,
        ),
    }

    report["ollama_context"] = {
        "summary": (
            "Panel interno de Plantaria calculado con pandas. "
            f"{kpis['events_7d']} eventos en 7 dias, "
            f"{kpis['reports_7d']} reportes nuevos, "
            f"{kpis['observations_7d']} observaciones nuevas, "
            f"{kpis['pending_records']} registros pendientes y "
            f"{kpis['open_flags']} flags abiertos."
        ),
        "kpis": kpis,
        "risk_signals": report["risk_signals"],
        "top_searches": report["top_searches"][:5],
        "top_creators": report["top_creators"][:5],
    }

    return report


def main() -> None:
    parser = argparse.ArgumentParser(description="Build Plantaria admin analytics with pandas.")
    parser.add_argument("--input", required=True, help="Directory containing exported CSV datasets.")
    parser.add_argument("--output", required=True, help="JSON report path to write.")
    args = parser.parse_args()

    input_dir = Path(args.input)
    output_path = Path(args.output)
    output_path.parent.mkdir(parents=True, exist_ok=True)

    report = build_report(input_dir)

    with output_path.open("w", encoding="utf-8") as handle:
        json.dump(report, handle, ensure_ascii=False, indent=2)

    print(f"Admin analytics written to {output_path}")


if __name__ == "__main__":
    main()
