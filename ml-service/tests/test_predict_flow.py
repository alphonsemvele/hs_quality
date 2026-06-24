import pytest
from fastapi.testclient import TestClient

from app.config import Settings, get_settings
from app.main import app


@pytest.fixture(autouse=True)
def _no_poll_delay():
    app.dependency_overrides[get_settings] = lambda: Settings(auth_secret="", poll_grace_seconds=0.0)
    yield
    app.dependency_overrides.pop(get_settings, None)


client = TestClient(app)


def test_health_check() -> None:
    response = client.get("/health")
    assert response.status_code == 200
    assert response.json() == {"status": "ok"}


def test_submit_then_poll_burnout_prediction() -> None:
    submit = client.post(
        "/predict",
        json={
            "type": "risque_burnout",
            "payload": {"overtime_hours_last_4_weeks": 50, "qvct_score": 3},
        },
    )
    assert submit.status_code == 200
    job_id = submit.json()["job_id"]

    poll = client.get(f"/predict/{job_id}")
    assert poll.status_code == 200
    result = poll.json()["result"]
    assert result["niveau"] in {"faible", "modere", "eleve", "critique"}
    assert 0.0 <= result["score"] <= 1.0


def test_submit_then_poll_report_analysis_prediction() -> None:
    submit = client.post(
        "/predict",
        json={
            "type": "analyse_rapport",
            "payload": {"rapport_texte": "Madame a fait une chute ce matin, plainte de douleur."},
        },
    )
    job_id = submit.json()["job_id"]

    poll = client.get(f"/predict/{job_id}")
    assert poll.status_code == 200
    themes = poll.json()["result"]["themes_detectes"]
    assert "chute" in themes
    assert "douleur" in themes


def test_unknown_job_id_returns_404() -> None:
    response = client.get("/predict/does-not-exist")
    assert response.status_code == 404


def test_invalid_prediction_type_returns_422() -> None:
    response = client.post("/predict", json={"type": "not_a_real_type", "payload": {}})
    assert response.status_code == 422
