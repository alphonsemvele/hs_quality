from fastapi.testclient import TestClient

from app.config import Settings, get_settings
from app.main import app


def test_request_rejected_without_token_when_secret_configured() -> None:
    app.dependency_overrides[get_settings] = lambda: Settings(auth_secret="super-secret", poll_grace_seconds=0.0)
    try:
        client = TestClient(app)
        response = client.post("/predict", json={"type": "risque_burnout", "payload": {}})
        assert response.status_code == 401
    finally:
        app.dependency_overrides.pop(get_settings, None)


def test_request_accepted_with_matching_token() -> None:
    app.dependency_overrides[get_settings] = lambda: Settings(auth_secret="super-secret", poll_grace_seconds=0.0)
    try:
        client = TestClient(app)
        response = client.post(
            "/predict",
            json={"type": "risque_burnout", "payload": {}},
            headers={"Authorization": "Bearer super-secret"},
        )
        assert response.status_code == 200
    finally:
        app.dependency_overrides.pop(get_settings, None)
