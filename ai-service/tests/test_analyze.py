"""Smoke tests for the AUREX AI microservice."""

from __future__ import annotations

import io

from fastapi.testclient import TestClient
from PIL import Image

from app.main import app

client = TestClient(app)


def _jpeg_bytes(color: tuple[int, int, int] = (123, 90, 64)) -> bytes:
    buf = io.BytesIO()
    Image.new("RGB", (256, 320), color).save(buf, format="JPEG", quality=80)
    return buf.getvalue()


def test_health_endpoint() -> None:
    r = client.get("/health")
    assert r.status_code == 200
    body = r.json()
    assert body["status"] == "ok"
    assert body["service"] == "aurex-ai"


def test_analyze_returns_expected_shape() -> None:
    r = client.post(
        "/analyze",
        files={"image": ("selfie.jpg", _jpeg_bytes(), "image/jpeg")},
    )
    assert r.status_code == 200, r.text
    body = r.json()
    for key in ("face_shape", "skin_undertone", "style_score", "hairstyles", "colors", "outfits"):
        assert key in body
    assert 0 <= body["style_score"] <= 100
    assert body["face_shape"] in {"oval", "round", "square", "heart", "oblong"}
    assert body["skin_undertone"] in {"warm", "cool", "neutral"}
    assert len(body["hairstyles"]) >= 1
    assert len(body["colors"]) >= 1
    assert len(body["outfits"]) >= 1


def test_analyze_deterministic_for_same_input() -> None:
    img = _jpeg_bytes((100, 80, 60))
    r1 = client.post("/analyze", files={"image": ("a.jpg", img, "image/jpeg")}).json()
    r2 = client.post("/analyze", files={"image": ("a.jpg", img, "image/jpeg")}).json()
    assert r1 == r2


def test_analyze_rejects_non_image() -> None:
    r = client.post("/analyze", files={"image": ("note.txt", b"hello world", "text/plain")})
    assert r.status_code == 400
