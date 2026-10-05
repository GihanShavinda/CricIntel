from fastapi.testclient import TestClient

from app.main import app


client = TestClient(app)


def test_health_declares_no_llm_and_no_gambling_use():
    response = client.get("/health")
    assert response.status_code == 200

    payload = response.json()
    assert payload["llm_enabled"] is False
    assert payload["gambling_use"] is False
