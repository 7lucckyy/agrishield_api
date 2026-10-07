from __future__ import annotations

from fastapi.testclient import TestClient

from main import create_app


class FakeRegistry:
    _transcribers: dict[str, object] = {}

    def transcribe(self, language: str, audio_path: str) -> str:
        assert language == "ha"
        assert audio_path.endswith(".m4a")

        return "Ganyen masar ya fara canza launi."


class GatedModelRegistry:
    _transcribers: dict[str, object] = {}

    def transcribe(self, language: str, audio_path: str) -> str:
        raise RuntimeError("You are trying to access a gated repo and are not in the authorized list.")


def test_transcribe_requires_valid_service_credentials(monkeypatch) -> None:
    monkeypatch.setenv("NATLAS_API_KEY", "local-test-key")
    client = TestClient(create_app(FakeRegistry()))

    response = client.post("/v1/transcriptions/ha", content=b"audio", headers={"content-type": "audio/mp4"})

    assert response.status_code == 401


def test_transcribe_returns_official_model_metadata(monkeypatch) -> None:
    monkeypatch.setenv("NATLAS_API_KEY", "local-test-key")
    client = TestClient(create_app(FakeRegistry()))

    response = client.post(
        "/v1/transcriptions/ha",
        content=b"audio",
        headers={"authorization": "Bearer local-test-key", "content-type": "audio/mp4"},
    )

    assert response.status_code == 200
    assert response.json()["text"] == "Ganyen masar ya fara canza launi."
    assert response.json()["model"] == "NCAIR1/Hausa-ASR"
    assert response.headers["x-request-id"]


def test_transcribe_explains_when_model_access_has_not_been_granted(monkeypatch) -> None:
    monkeypatch.setenv("NATLAS_API_KEY", "local-test-key")
    client = TestClient(create_app(GatedModelRegistry()))

    response = client.post(
        "/v1/transcriptions/ha",
        content=b"audio",
        headers={"authorization": "Bearer local-test-key", "content-type": "audio/mp4"},
    )

    assert response.status_code == 503
    assert response.json()["detail"] == "Access to the selected official N-ATLaS ASR model has not been granted to this Hugging Face account."
