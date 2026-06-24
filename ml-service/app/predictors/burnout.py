from typing import Any

from app.predictors.shared import risk_level, to_float


class BurnoutPredictor:
    """Placeholder heuristic for `risque_burnout` (subject: intervenant).

    Replace with a trained scikit-learn model once enough labelled outcomes
    (intervenants who actually left/burned out vs. didn't) accumulate in
    production — there's no training data yet, so a rule-based score is the
    honest starting point rather than a model fit to nothing.

    Expected (optional) payload keys, all numeric:
      hours_per_week, overtime_hours_last_4_weeks, sick_days_last_90_days,
      qvct_score (0-10, lower = worse), missed_or_late_interventions_30_days
    """

    def predict(self, payload: dict[str, Any]) -> dict[str, Any]:
        hours = to_float(payload.get("hours_per_week"), default=35.0)
        overtime = to_float(payload.get("overtime_hours_last_4_weeks"), default=0.0)
        sick_days = to_float(payload.get("sick_days_last_90_days"), default=0.0)
        qvct_score = to_float(payload.get("qvct_score"), default=7.0)
        missed = to_float(payload.get("missed_or_late_interventions_30_days"), default=0.0)

        score = 0.0
        score += min(overtime / 40.0, 1.0) * 0.30
        score += min(sick_days / 15.0, 1.0) * 0.25
        score += max(0.0, (7.0 - qvct_score) / 7.0) * 0.30
        score += min(missed / 10.0, 1.0) * 0.10
        score += min(max(0.0, hours - 40.0) / 20.0, 1.0) * 0.05
        score = round(min(score, 1.0), 3)

        return {
            "score": score,
            "niveau": risk_level(score),
            "facteurs": {
                "heures_supplementaires": overtime,
                "jours_arret_90j": sick_days,
                "score_qvct": qvct_score,
                "interventions_manquees_30j": missed,
            },
            "modele": "heuristique_v0",
        }
