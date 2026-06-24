from typing import Any

from app.predictors.shared import risk_level

# Each theme's keyword list is intentionally simple substring matching, not
# NLP — a placeholder for the transformer-based semantic analysis called for
# in the CDC. Swap THEMES for a real classifier once it's trained; the
# output shape (themes_detectes / score / niveau) can stay the same so
# Laravel-side consumers don't need to change.
THEMES: dict[str, list[str]] = {
    "chute": ["chute", "tombe", "tombée", "glissé"],
    "douleur": ["douleur", "souffre", "mal au", "plainte"],
    "confusion": ["confus", "désorienté", "désorientée", "perdu ses repères"],
    "refus_de_soins": ["refuse", "refus de", "s'oppose"],
    "agressivite": ["agressif", "agressive", "violence", "crie"],
    "isolement": ["isolé", "isolée", "seul toute la journée", "ne voit personne"],
}


class ReportAnalysisPredictor:
    """Placeholder heuristic for `analyse_rapport` (subject: intervention).

    Expected payload key: `rapport_texte` (str) — the free-text intervention
    report. Falls back to an empty-themes result if absent rather than
    erroring, since this predictor is informational, not safety-blocking.
    """

    def predict(self, payload: dict[str, Any]) -> dict[str, Any]:
        text = str(payload.get("rapport_texte") or "").lower()

        detected = [theme for theme, keywords in THEMES.items() if any(kw in text for kw in keywords)]
        score = round(min(len(detected) / 3.0, 1.0), 3)

        return {
            "score": score,
            "niveau": risk_level(score),
            "themes_detectes": detected,
            "modele": "mots_cles_v0",
        }
