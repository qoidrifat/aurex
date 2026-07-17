# AUREX AI microservice

A tiny FastAPI service that pretends to do face analysis. It accepts a selfie
and returns a deterministic style profile (face shape, skin undertone, style
score, hairstyle / color / outfit suggestions). The Laravel backend hits this
service from `App\Services\AurexAiClient`.

This is a scaffold — swap the mock in `app/analysis.py` for a real model when
you're ready (MediaPipe Face Mesh, OpenCV DNN, a fine-tuned CNN, etc.).

## Run locally

```bash
cd ai-service
python -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --host 127.0.0.1 --port 8001 --reload
```

Make sure the Laravel app has `AUREX_AI_URL=http://127.0.0.1:8001` in its
`.env` (already the default in `.env.example`).

## Run with Docker

```bash
docker build -t aurex-ai .
docker run --rm -p 8001:8001 aurex-ai
```

Or via the root `docker-compose.yml`:

```bash
docker compose up ai
```

## Test

```bash
pip install pytest
PYTHONPATH=. pytest
```

## API

### `GET /health`

```json
{ "status": "ok", "service": "aurex-ai", "version": "0.1.0" }
```

### `POST /analyze`

Multipart form upload. Field name: `image`. Returns:

```json
{
  "face_shape": "oval",
  "skin_undertone": "warm",
  "style_score": 82,
  "hairstyles": ["textured quiff", "mid fade"],
  "colors": ["olive", "camel", "rust"],
  "outfits": ["olive tee + black jeans"],
  "meta": { "source": "mock", "image_size": "1024x1024" }
}
```
