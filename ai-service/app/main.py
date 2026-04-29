"""AUREX AI microservice.

Mock face-analysis endpoint used by the Laravel backend. It accepts an uploaded
image, verifies it is a valid image, and returns a deterministic (seeded by
image hash) style profile so the frontend can demonstrate the full flow without
a real ML model in the loop.

Swap the mock logic in `app.analysis.analyze_image` for a real model when
ready (MediaPipe, OpenCV, a fine-tuned CNN, etc.).
"""

from __future__ import annotations

from fastapi import FastAPI, File, HTTPException, UploadFile
from fastapi.middleware.cors import CORSMiddleware

from .analysis import analyze_image
from .schemas import AnalyzeResponse, HealthResponse

app = FastAPI(
    title="AUREX AI",
    description="Face-analysis microservice for the AUREX platform.",
    version="0.1.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=False,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.get("/", response_model=HealthResponse)
@app.get("/health", response_model=HealthResponse)
async def health() -> HealthResponse:
    return HealthResponse(status="ok", service="aurex-ai", version="0.1.0")


@app.post("/analyze", response_model=AnalyzeResponse)
async def analyze(image: UploadFile = File(...)) -> AnalyzeResponse:
    if image.content_type is None or not image.content_type.startswith("image/"):
        raise HTTPException(status_code=400, detail="Uploaded file must be an image.")

    payload = await image.read()
    if not payload:
        raise HTTPException(status_code=400, detail="Empty image upload.")

    if len(payload) > 12 * 1024 * 1024:
        raise HTTPException(status_code=413, detail="Image is too large (max 12 MB).")

    try:
        return analyze_image(payload)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
