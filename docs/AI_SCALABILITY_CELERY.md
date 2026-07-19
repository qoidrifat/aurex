# 🚀 AI Service Scalability — Celery Task Queue Architecture
## Item #2 Prioritas Tinggi: Skalabilitas AI Service → Async

### Masalah

Saat ini `face_mesh.process()` berjalan **synchronous** di dalam FastAPI.
Pada 100+ concurrent users, service akan kehabisan resource karena:
- Setiap request memblokir worker hingga selesai (5-15 detik)
- Hanya ada 2 worker (`--workers 2`)
- Tidak ada queue mechanism — request langsung diproses atau timeout

### Arsitektur Target

```
┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐
│  Mobile  │ ──► │ Laravel  │ ──► │  Redis   │ ──► │  Celery  │
│  / Web   │     │ Backend  │     │ (Broker) │     │  Worker  │
└──────────┘     └──────────┘     └──────────┘     └──────────┘
                                              │
                                              ▼
                                        ┌──────────┐
                                        │  Celery  │
                                        │  Worker  │
                                        └──────────┘
                                              │
                                              ▼
                                        ┌──────────┐
                                        │ FastAPI   │
                                        │ (polling) │
                                        └──────────┘
```

### Flow Async (Polling)

1. **Mobile** → `POST /api/v1/analyze` → Laravel menyimpan task ke Redis
2. **Laravel** → Respond `202 Accepted` dengan `task_id`
3. **Celery Worker** → Ambil task dari Redis → Proses face analysis
4. **Mobile** → Polling `GET /api/v1/analyze/{task_id}/status` setiap 5 detik
5. **Celery Worker** → Selesai → Update status + simpan hasil
6. **Mobile** → Terima result

### File yang Perlu Dimodifikasi / Dibuat

#### 1. AI Service — `ai_service/python_ai/`

| File | Deskripsi |
|------|-----------|
| `celery_app.py` (baru) | Inisialisasi Celery app, konek ke Redis |
| `tasks.py` (baru) | Task `analyze_face_task()` — panggil fungsi existing |
| `celery_requirements.txt` (baru) | Tambah `celery[redis]` |
| `Dockerfile` (update) | Tambah `celery -A celery_app worker` sebagai service |

#### 2. Backend — `backend/laravel_api/`

| File | Deskripsi |
|------|-----------|
| `app/Jobs/AnalyzeImageJob.php` (baru) | Laravel Job untuk trigger Celery task |
| `app/Http/Controllers/AnalysisController.php` (update) | Ubah `analyze()` jadi async |
| `routes/api.php` (update) | Tambah endpoint status polling |
| Database migration (baru) | Tambah kolom `job_id` + `status` ke `analyses` |

### Kode Referensi

#### `celery_app.py`

```python
from celery import Celery
import os

redis_url = os.environ.get("CELERY_BROKER_URL", "redis://redis:6379/0")

celery_app = Celery(
    "aurex_ai",
    broker=redis_url,
    backend=redis_url,
)

celery_app.conf.update(
    task_serializer="json",
    accept_content=["json"],
    result_serializer="json",
    timezone="UTC",
    enable_utc=True,
    task_track_started=True,
    task_acks_late=True,
    worker_prefetch_multiplier=1,
    task_soft_time_limit=30,
    task_time_limit=60,
)
```

#### `tasks.py`

```python
from celery_app import celery_app
from main import validate_image_sync, detect_face, classify_face_shape, \
    detect_skin_undertone, calculate_face_symmetry, calculate_style_score, \
    get_recommendations
import time
import logging

logger = logging.getLogger("aurex-ai-celery")

@celery_app.task(bind=True, max_retries=3, default_retry_delay=5)
def analyze_face_task(self, image_bytes: bytes, filename: str):
    """
    Celery task untuk analisis wajah secara asynchronous.
    Memanggil fungsi-fungsi yang sudah ada di main.py.
    """
    logger.info(f"Processing task {self.request.id} for {filename}")

    import cv2
    import numpy as np

    nparr = np.frombuffer(image_bytes, np.uint8)
    img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
    img_rgb = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)

    landmarks = detect_face(img_rgb)
    if landmarks is None:
        raise ValueError("No face detected")

    face_shape, shape_conf = classify_face_shape(landmarks)
    undertone, undertone_conf = detect_skin_undertone(img_rgb)
    symmetry = calculate_face_symmetry(landmarks)
    avg_conf = round((shape_conf + undertone_conf) / 2, 2)
    score, scoring_conf = calculate_style_score(face_shape, undertone, symmetry, avg_conf)
    recs = get_recommendations(face_shape, undertone)

    return {
        "face_shape": face_shape,
        "undertone": undertone,
        "style_score": score,
        "confidence": scoring_conf,
        "hairstyles": recs["hairstyles"],
        "colors": recs["colors"],
        "outfits": recs["outfits"],
        "model_version": "2.0.0",
    }
```

### Migration Plan

| Langkah | Durasi | Risiko |
|---------|--------|--------|
| 1. Setup Celery + Redis (AI Service) | 4-6 jam | Rendah — Redis sudah ada |
| 2. Buat Laravel Job + polling endpoint | 3-4 jam | Rendah |
| 3. Update Flutter untuk polling result | 4-6 jam | Sedang — perlu loading state |
| 4. Testing async flow end-to-end | 2-3 jam | Rendah |
| 5. Deploy staging + monitoring | 2-3 jam | Rendah |

**Total estimasi: 15-22 jam pengembangan**

### Catatan

- Implementasi **Celery** bersifat opsional untuk MVP/traffic rendah (<50 concurrent users)
- Untuk saat ini, mekanisme **retry 3x + exponential backoff** sudah cukup
- Saat traffic meningkat, aktifkan Celery dengan mengikuti panduan di atas
- Alternatif yang lebih ringan: **Redis Queue (RQ)** atau **Dramatiq** jika Celery terlalu berat
