from typing import Any

from app.predictors.shared import risk_level, to_float


class AutonomyLossPredictor:
    """Placeholder heuristic for `perte_autonomie` (subject: beneficiaire).

    Same caveat as BurnoutPredictor: rule-based until real outcome data
    (GIR re-evaluations over time) exists to train on.

    Expected (optional) payload keys:
      gir (1-6, lower = more dependent), recent_incidents_count_90_days,
      plan_adherence_rate (0-1), age
    """

    def predict(self, payload: dict[str, Any]) -> dict[str, Any]:
        gir = to_float(payload.get("gir"), default=4.0)
        incidents = to_float(payload.get("recent_incidents_count_90_days"), default=0.0)
        adherence = to_float(payload.get("plan_adherence_rate"), default=1.0)
        age = to_float(payload.get("age"), default=75.0)

        score = 0.0
        score += max(0.0, (4.0 - gir) / 3.0) * 0.35
        score += min(incidents / 5.0, 1.0) * 0.30
        score += max(0.0, 1.0 - adherence) * 0.20
        score += min(max(0.0, age - 75.0) / 25.0, 1.0) * 0.15
        score = round(min(score, 1.0), 3)

        return {
            "score": score,
            "niveau": risk_level(score),
            "facteurs": {
                "gir": gir,
                "incidents_90j": incidents,
                "taux_adherence_plan": adherence,
                "age": age,
            },
            "modele": "heuristique_v0",
        }
