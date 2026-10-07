from __future__ import annotations

import os
import tempfile
import uuid
from pathlib import Path
from threading import Lock
from typing import Any

from fastapi import FastAPI, HTTPException, Request
from fastapi.responses import JSONResponse

MODEL_IDS = {
    "en": "NCAIR1/NigerianAccentedEnglish",
    "ha": "NCAIR1/Hausa-ASR",
    "yo": "NCAIR1/Yoruba-ASR",
    "ig": "NCAIR1/Igbo-ASR",
}

MAX_AUDIO_BYTES = 20 * 1024 * 1024

MIME_EXTENSIONS = {
    "audio/mpeg": ".mp3",
    "audio/mp4": ".m4a",
    "audio/x-m4a": ".m4a",
    "audio/wav": ".wav",
    "audio/x-wav": ".wav",
    "audio/ogg": ".ogg",
    "audio/webm": ".webm",
    "video/webm": ".webm",
}


class TranscriberRegistry:
    def __init__(self) -> None:
        self._transcribers: dict[str, Any] = {}
        self._lock = Lock()

    def transcribe(self, language: str, audio_path: str) -> str:
        transcriber = self._get(language)
        result = transcriber(audio_path)
        transcript = str(result.get("text", "")).strip()
        if not transcript:
            raise RuntimeError("N-ATLaS returned an empty transcript.")

        return transcript

    def _get(self, language: str) -> Any:
        if language in self._transcribers:
            return self._transcribers[language]

        with self._lock:
            if language in self._transcribers:
                return self._transcribers[language]

            from transformers import pipeline

            device = self._device()
            self._transcribers[language] = pipeline(
                "automatic-speech-recognition",
                model=MODEL_IDS[language],
                token=os.environ.get("HF_TOKEN") or None,
                device=device,
            )

        return self._transcribers[language]

    @staticmethod
    def _device() -> int | str:
        import torch

        if torch.cuda.is_available():
            return 0
        if torch.backends.mps.is_available():
            return "mps"

        return -1


def create_app(registry: TranscriberRegistry | None = None) -> FastAPI:
    application = FastAPI(title="AgriShield N-ATLaS Voice Service")
    application.state.registry = registry or TranscriberRegistry()

    @application.get("/health")
    async def health() -> dict[str, object]:
        return {
            "status": "ok",
            "supported_languages": list(MODEL_IDS),
            "loaded_languages": list(application.state.registry._transcribers),
        }

    @application.post("/v1/transcriptions/{language}")
    async def transcribe(language: str, request: Request) -> JSONResponse:
        _authorize(request)
        if language not in MODEL_IDS:
            raise HTTPException(status_code=422, detail="Supported languages are en, ha, yo and ig.")

        audio = await request.body()
        if not audio:
            raise HTTPException(status_code=422, detail="An audio request body is required.")
        if len(audio) > MAX_AUDIO_BYTES:
            raise HTTPException(status_code=413, detail="Audio must not exceed 20 MB.")

        suffix = MIME_EXTENSIONS.get(request.headers.get("content-type", "").split(";", 1)[0].lower(), ".bin")
        audio_path = _write_temporary_audio(audio, suffix)
        request_id = str(uuid.uuid4())

        try:
            transcript = application.state.registry.transcribe(language, audio_path)
        except HTTPException:
            raise
        except Exception as exception:
            if _is_model_access_error(exception):
                raise HTTPException(
                    status_code=503,
                    detail='Access to the selected official N-ATLaS ASR model has not been granted to this Hugging Face account.',
                ) from exception

            raise HTTPException(status_code=502, detail="N-ATLaS transcription failed.") from exception
        finally:
            Path(audio_path).unlink(missing_ok=True)

        return JSONResponse(
            content={
                "text": transcript,
                "language": language,
                "model": MODEL_IDS[language],
                "request_id": request_id,
            },
            headers={"X-Request-ID": request_id},
        )

    return application


def _authorize(request: Request) -> None:
    api_key = os.environ.get("NATLAS_API_KEY")
    if not api_key:
        raise HTTPException(status_code=503, detail="NATLAS_API_KEY is not configured.")

    if request.headers.get("authorization") != f"Bearer {api_key}":
        raise HTTPException(status_code=401, detail="Invalid service credentials.")


def _write_temporary_audio(audio: bytes, suffix: str) -> str:
    with tempfile.NamedTemporaryFile(suffix=suffix, delete=False) as file:
        file.write(audio)

        return file.name


def _is_model_access_error(exception: Exception) -> bool:
    message = str(exception).lower()

    return "gated repo" in message or "not in the authorized list" in message


app = create_app()
