from typing import Any


def to_float(value: Any, *, default: float) -> float:
    try:
        return float(value) if value is not None else default
    except (TypeError, ValueError):
        return default


def risk_level(score: float) -> str:
    if score >= 0.75:
        return "critique"
    if score >= 0.5:
        return "eleve"
    if score >= 0.25:
        return "modere"
    return "faible"
