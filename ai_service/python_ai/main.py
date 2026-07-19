import os
import time
import logging
from typing import Dict, List, Optional, Tuple

import cv2
import mediapipe as mp
import numpy as np
from fastapi import FastAPI, UploadFile, File, HTTPException, Depends, Security
from fastapi.security import APIKeyHeader
from fastapi.middleware.cors import CORSMiddleware
import uvicorn

# ==================== CONFIGURATION ====================

MODEL_VERSION = "2.0.0"
MAX_FILE_SIZE = 10 * 1024 * 1024  # 10 MB
ALLOWED_CONTENT_TYPES = {"image/jpeg", "image/png"}
ALLOWED_EXTENSIONS = {".jpg", ".jpeg", ".png"}
MIN_IMAGE_DIMENSION = 200
MAX_IMAGE_DIMENSION = 4000
API_KEY_NAME = "X-API-Key"

# ==================== LOGGING ====================

# Structured JSON logging (mirip Laravel json channel)
class StructuredFormatter(logging.Formatter):
    """Format log dalam JSON untuk observability."""
    def format(self, record: logging.LogRecord) -> str:
        import json
        log_entry = {
            "timestamp": self.formatTime(record, "%Y-%m-%dT%H:%M:%S.%fZ"),
            "level": record.levelname,
            "service": "aurex-ai",
            "version": MODEL_VERSION,
            "message": record.getMessage(),
        }
        if record.exc_info and record.exc_info[0]:
            log_entry["exception"] = self.formatException(record.exc_info)
        if hasattr(record, "extra_fields"):
            log_entry.update(record.extra_fields)
        return json.dumps(log_entry, ensure_ascii=False)


# Cek apakah output JSON atau plain text berdasarkan env
json_logging = os.environ.get("AI_JSON_LOG", "false").lower() == "true"

if json_logging:
    handler = logging.StreamHandler()
    handler.setFormatter(StructuredFormatter())
    logging.basicConfig(level=logging.INFO, handlers=[handler])
else:
    logging.basicConfig(
        level=logging.INFO,
        format="%(asctime)s | %(levelname)s | %(message)s",
    )

logger = logging.getLogger("aurex-ai")


def log_with_context(message: str, level: str = "info", **kwargs):
    """Log dengan konteks tambahan (extra_fields)."""
    extra = logging.LogRecord(
        name=logger.name,
        level=0,
        pathname="",
        lineno=0,
        msg="",
        args=(),
        exc_info=None,
    )
    extra.extra_fields = kwargs
    getattr(logger, level)(message, extra={"extra_fields": kwargs})

# ==================== MEDIAPIPE INIT ====================

mp_face_mesh = mp.solutions.face_mesh
mp_face_detection = mp.solutions.face_detection

# Track MediaPipe & OpenCV versions untuk model versioning (#4 Prioritas Sedang)
MEDIAPIPE_VERSION = getattr(mp, "__version__", "unknown")
OPENCV_VERSION = cv2.__version__

logger.info(
    f"Initializing models | MediaPipe v{MEDIAPIPE_VERSION} "
    f"OpenCV v{OPENCV_VERSION} "
    f"Service v{MODEL_VERSION}"
)

face_mesh = mp_face_mesh.FaceMesh(
    static_image_mode=True,
    max_num_faces=1,
    min_detection_confidence=0.5,
)

face_detection = mp_face_detection.FaceDetection(
    model_selection=1,  # 1 = full range (best for close-ups)
    min_detection_confidence=0.5,
)

# ==================== API KEY AUTH ====================

api_key_header = APIKeyHeader(name=API_KEY_NAME, auto_error=False)

def verify_api_key(api_key: Optional[str] = Depends(api_key_header)) -> str:
    """Verifikasi API key dari header request."""
    expected_key = os.environ.get("AI_SERVICE_API_KEY", "")
    
    # Jika tidak ada API key yang dikonfigurasi, izinkan semua request (dev mode)
    if not expected_key:
        logger.warning("AI_SERVICE_API_KEY not configured - running in DEVELOPMENT mode")
        return "dev-mode"
    
    if not api_key:
        raise HTTPException(
            status_code=401,
            detail="Missing API key. Provide it via X-API-Key header.",
        )
    
    if api_key != expected_key:
        raise HTTPException(
            status_code=403,
            detail="Invalid API key.",
        )
    
    return api_key

# ==================== FASTAPI APP ====================

app = FastAPI(
    title="AUREX AI Service",
    version=MODEL_VERSION,
    description="AI-powered face analysis and style recommendation engine",
)

# CORS untuk development
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ==================== INPUT VALIDATION ====================

async def validate_image(file: UploadFile) -> Tuple[np.ndarray, int, int]:
    """
    Validasi file gambar yang diupload.
    Returns: (image_array, height, width)
    Raises: HTTPException jika validasi gagal
    """
    # Validasi content type
    if file.content_type not in ALLOWED_CONTENT_TYPES:
        raise HTTPException(
            status_code=400,
            detail=f"Format file tidak didukung. Gunakan: JPEG atau PNG. Diterima: {file.content_type}",
        )
    
    # Validasi ekstensi file
    filename = file.filename or ""
    ext = os.path.splitext(filename)[1].lower()
    if ext not in ALLOWED_EXTENSIONS:
        raise HTTPException(
            status_code=400,
            detail=f"Ekstensi file tidak didukung: {ext}. Gunakan: .jpg, .jpeg, atau .png",
        )
    
    # Baca file
    contents = await file.read()
    
    # Validasi ukuran file
    if len(contents) > MAX_FILE_SIZE:
        raise HTTPException(
            status_code=400,
            detail=f"Ukuran file terlalu besar. Maksimal {MAX_FILE_SIZE // (1024*1024)}MB.",
        )
    
    # Decode gambar
    nparr = np.frombuffer(contents, np.uint8)
    img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
    
    if img is None:
        raise HTTPException(
            status_code=400,
            detail="File gambar tidak valid atau corrupt.",
        )
    
    h, w, _ = img.shape
    
    # Validasi dimensi
    if h < MIN_IMAGE_DIMENSION or w < MIN_IMAGE_DIMENSION:
        raise HTTPException(
            status_code=400,
            detail=f"Gambar terlalu kecil. Minimal {MIN_IMAGE_DIMENSION}x{MIN_IMAGE_DIMENSION} pixel.",
        )
    
    if h > MAX_IMAGE_DIMENSION or w > MAX_IMAGE_DIMENSION:
        raise HTTPException(
            status_code=400,
            detail=f"Gambar terlalu besar. Maksimal {MAX_IMAGE_DIMENSION}x{MAX_IMAGE_DIMENSION} pixel.",
        )
    
    return img, h, w

# ==================== FACE DETECTION & ANALYSIS ====================

def detect_face(img_rgb: np.ndarray) -> Optional[Tuple]:
    """
    Deteksi wajah menggunakan MediaPipe Face Mesh.
    Returns: List of landmark objects atau None jika tidak ada wajah terdeteksi.
    """
    results = face_mesh.process(img_rgb)
    
    if not results.multi_face_landmarks:
        return None
    
    return results.multi_face_landmarks[0].landmark

def classify_face_shape(landmarks) -> Tuple[str, float]:
    """
    Klasifikasi bentuk wajah berdasarkan landmark MediaPipe.
    Returns: (shape_name, confidence)
    """
    def get_dist(p1, p2):
        return np.linalg.norm(np.array([p1.x, p1.y]) - np.array([p2.x, p2.y]))
    
    # Landmarks utama
    p10 = landmarks[10]   # Top of forehead
    p152 = landmarks[152] # Chin bottom
    p234 = landmarks[234] # Left cheek
    p454 = landmarks[454] # Right cheek
    p103 = landmarks[103] # Left forehead
    p332 = landmarks[332] # Right forehead
    p172 = landmarks[172] # Left jaw
    p397 = landmarks[397] # Right jaw
    p33 = landmarks[33]   # Left temple
    p263 = landmarks[263] # Right temple
    
    face_height = get_dist(p10, p152)
    face_width = get_dist(p33, p263)
    forehead_width = get_dist(p103, p332)
    jaw_width = get_dist(p172, p397)
    cheek_width = get_dist(p234, p454)
    
    ratio = face_height / face_width if face_width > 0 else 1.0
    jaw_ratio = jaw_width / cheek_width if cheek_width > 0 else 1.0
    forehead_ratio = forehead_width / cheek_width if cheek_width > 0 else 1.0
    
    # Scoring untuk setiap bentuk wajah
    scores = {
        "oval": 0.0,
        "round": 0.0,
        "square": 0.0,
        "heart": 0.0,
        "rectangle": 0.0,
        "diamond": 0.0,
    }
    
    # Oval: rasio 1.3-1.5, jaw menyempit
    if 1.25 <= ratio <= 1.55 and jaw_ratio < 0.9:
        scores["oval"] = 0.9 if abs(ratio - 1.4) < 0.1 else 0.7
    
    # Round: rasio < 1.15, dahi dan rahang lebar
    if ratio < 1.15:
        scores["round"] = min(0.9, 0.5 + (1.15 - ratio) * 2)
    
    # Square: rasio 1.1-1.3, rahang lebar (jaw_ratio tinggi)
    if 1.1 <= ratio <= 1.3 and jaw_ratio > 0.85:
        scores["square"] = min(0.85, 0.3 + (jaw_ratio - 0.8) * 3)
    
    # Heart: dahi lebar, dagu sempit
    if forehead_ratio > 1.05 and jaw_ratio < 0.8:
        scores["heart"] = min(0.9, 0.3 + (forehead_ratio - 1.0) * 3)
    
    # Rectangle: rasio > 1.5, rahang lurus
    if ratio > 1.5 and jaw_ratio > 0.8:
        scores["rectangle"] = min(0.9, 0.3 + (ratio - 1.5) * 2)
    
    # Diamond: tulang pipi lebar, dahi dan dagu sempit
    if forehead_ratio < 0.95 and jaw_ratio < 0.85 and cheek_width > face_width * 0.5:
        scores["diamond"] = min(0.85, 0.2 + (1.0 - forehead_ratio) * 3)
    
    # Ambil skor tertinggi
    best_shape = max(scores, key=scores.get)
    confidence = scores[best_shape]
    
    # Fallback ke oval jika confidence terlalu rendah
    if confidence < 0.3:
        best_shape = "oval"
        confidence = 0.5
    
    return best_shape, round(confidence, 2)


def detect_skin_undertone(img_rgb: np.ndarray) -> Tuple[str, float]:
    """
    Deteksi skin undertone menggunakan analisis warna di beberapa region wajah.
    Menggunakan HSV color space untuk akurasi lebih baik.
    Returns: (undertone, confidence)
    """
    h, w, _ = img_rgb.shape
    
    # Sample beberapa region
    regions = [
        img_rgb[int(h*0.35):int(h*0.55), int(w*0.35):int(w*0.65)],  # Center (pipi)
        img_rgb[int(h*0.25):int(h*0.40), int(w*0.30):int(w*0.70)],  # Dahi
        img_rgb[int(h*0.55):int(h*0.70), int(w*0.35):int(w*0.65)],  # Dagu
    ]
    
    # Filter region yang valid
    valid_regions = [r for r in regions if r.size > 0]
    if not valid_regions:
        return "neutral", 0.5
    
    # Hitung rata-rata warna dari semua region
    avg_colors = [np.mean(r, axis=(0, 1)) for r in valid_regions]
    avg_color = np.mean(avg_colors, axis=0)
    
    r, g, b = avg_color
    
    # Konversi ke HSV untuk deteksi yang lebih akurat
    hsv_sample = np.uint8([[[b, g, r]]])  # OpenCV uses BGR
    hsv = cv2.cvtColor(hsv_sample, cv2.COLOR_RGB2HSV)[0][0]
    hue, sat, val = hsv
    
    # Analisis undertone berdasarkan kombinasi RGB dan HSV
    warm_score = 0.0
    cool_score = 0.0
    
    # Warm indicators
    if r > g and r > b:
        warm_score += 0.4
    if g > b and r > g:
        warm_score += 0.2
    if hue < 30 or (hue > 330 and hue < 360):
        warm_score += 0.3
    if r - b > 30:
        warm_score += 0.2
    
    # Cool indicators
    if b > r and b > g:
        cool_score += 0.4
    if g > r and b > g:
        cool_score += 0.2
    if 150 < hue < 270:
        cool_score += 0.3
    if b - r > 20:
        cool_score += 0.2
    
    # Normalisasi
    total = warm_score + cool_score
    if total == 0:
        return "neutral", 0.5
    
    warm_ratio = warm_score / total
    
    if warm_ratio > 0.6:
        return "warm", round(warm_ratio, 2)
    elif warm_ratio < 0.4:
        return "cool", round(1 - warm_ratio, 2)
    else:
        return "neutral", round(1 - abs(warm_ratio - 0.5) * 2, 2)


def calculate_face_symmetry(landmarks) -> float:
    """
    Hitung skor simetri wajah (0.0 - 1.0).
    Membandingkan posisi landmark kiri dan kanan.
    """
    # Pasangan landmark kiri-kanan
    pairs = [
        (33, 263),   # Mata kiri-kanan
        (61, 291),   # Mulut kiri-kanan
        (103, 332),  # Alis kiri-kanan
        (50, 280),   # Cuping hidung
        (172, 397),  # Rahang kiri-kanan
        (234, 454),  # Pipi kiri-kanan
    ]
    
    symmetry_scores = []
    for left_idx, right_idx in pairs:
        left = np.array([landmarks[left_idx].x, landmarks[left_idx].y])
        right = np.array([landmarks[right_idx].x, landmarks[right_idx].y])
        
        # Idealnya: mirrored horizontally
        diff = abs(left[0] - (1.0 - right[0])) + abs(left[1] - right[1])
        score = max(0, 1.0 - diff * 2)
        symmetry_scores.append(score)
    
    return round(np.mean(symmetry_scores), 2)


def calculate_style_score(
    face_shape: str,
    undertone: str,
    symmetry: float,
    confidence: float,
) -> Tuple[int, float]:
    """
    Hitung style score berdasarkan beberapa faktor:
    - Face symmetry: 30% (seberapa simetris wajah)
    - Face proportion: 30% (kesesuaian dengan bentuk ideal)
    - Undertone match: 20% (kesesuaian undertone dengan rekomendasi)
    - Feature harmony: 20% (harmoni fitur wajah)
    
    Returns: (score 0-100, confidence 0.0-1.0)
    """
    # 1. Symmetry score (30%)
    symmetry_weight = 0.30
    symmetry_score = symmetry * 100
    
    # 2. Proportion score (30%)
    proportion_weight = 0.30
    # Oval dan diamond dianggap proporsi ideal
    ideal_shapes = ["oval", "diamond"]
    good_shapes = ["square", "heart"]
    if face_shape in ideal_shapes:
        proportion_score = 85 + confidence * 10
    elif face_shape in good_shapes:
        proportion_score = 70 + confidence * 15
    else:
        proportion_score = 60 + confidence * 20
    proportion_score = min(100, proportion_score)
    
    # 3. Undertone match score (20%)
    undertone_weight = 0.20
    warm_shapes = ["oval", "heart", "round"]
    cool_shapes = ["square", "rectangle", "diamond"]
    if (undertone == "warm" and face_shape in warm_shapes) or \
       (undertone == "cool" and face_shape in cool_shapes):
        undertone_score = 85 + confidence * 10
    elif undertone == "neutral":
        undertone_score = 80
    else:
        undertone_score = 65 + confidence * 15
    undertone_score = min(100, undertone_score)
    
    # 4. Feature harmony (20%)
    harmony_weight = 0.20
    # Kombinasi symmetry + confidence (0-100)
    harmony_score = min(100, symmetry * 60 + confidence * 40)
    
    # Hitung total
    total_score = (
        symmetry_score * symmetry_weight +
        proportion_score * proportion_weight +
        undertone_score * undertone_weight +
        harmony_score * harmony_weight
    )
    
    total_score = round(max(0, min(100, total_score)))
    
    # Confidence keseluruhan
    overall_confidence = round(
        (symmetry * 0.3 + confidence * 0.4 + (total_score / 100) * 0.3),
        2,
    )
    
    return total_score, overall_confidence


def get_recommendations(face_shape: str, undertone: str) -> Dict:
    """Generate rekomendasi gaya berdasarkan bentuk wajah dan undertone."""
    recommendations = {
        "oval": {
            "hairstyles": [
                "textured quiff",
                "mid fade with fringe",
                "classic side part",
                "layered medium length",
            ],
            "colors": {
                "warm": ["olive", "burgundy", "camel", "navy"],
                "cool": ["blue", "grey", "black", "charcoal"],
                "neutral": ["navy", "forest green", "maroon", "taupe"],
            },
            "outfits": [
                "structured blazer + neutral tee + slim chinos",
                "casual linen shirt + dark jeans + leather sneakers",
            ],
        },
        "round": {
            "hairstyles": [
                "high pompadour",
                "undercut with volume",
                "faux hawk",
                "slicked back with height",
            ],
            "colors": {
                "warm": ["charcoal", "dark brown", "rust", "deep orange"],
                "cool": ["navy", "black", "deep blue", "grey"],
                "neutral": ["forest green", "charcoal", "maroon", "taupe"],
            },
            "outfits": [
                "vertical-striped shirt + dark slim jeans + boots",
                "tailored vest + fitted tee + straight pants",
            ],
        },
        "square": {
            "hairstyles": [
                "buzz cut",
                "crew cut",
                "slick back undercut",
                "short textured crop",
            ],
            "colors": {
                "warm": ["camel", "beige", "forest green", "warm brown"],
                "cool": ["navy", "black", "deep blue", "slate grey"],
                "neutral": ["olive", "charcoal", "cream", "taupe"],
            },
            "outfits": [
                "henley shirt + cargo pants + combat boots",
                "leather jacket + white tee + dark jeans",
            ],
        },
        "heart": {
            "hairstyles": [
                "long fringe",
                "side swept with texture",
                "layered curls",
                "messy medium length",
            ],
            "colors": {
                "warm": ["pastel pink", "cream", "light brown", "coral"],
                "cool": ["light blue", "lavender", "grey", "white"],
                "neutral": ["taupe", "sage", "mauve", "cream"],
            },
            "outfits": [
                "v-neck sweater + straight pants + loafers",
                "open collared shirt + chinos + minimalist sneakers",
            ],
        },
        "rectangle": {
            "hairstyles": [
                "short brush up",
                "tapered sides with length on top",
                "classic fringe",
                "side-swept with volume",
            ],
            "colors": {
                "warm": ["deep red", "olive", "brown", "gold"],
                "cool": ["navy", "black", "charcoal", "deep blue"],
                "neutral": ["forest green", "maroon", "taupe", "grey"],
            },
            "outfits": [
                "double-breasted blazer + fitted tee + tailored pants",
                "layered sweater over collared shirt + slim jeans",
            ],
        },
        "diamond": {
            "hairstyles": [
                "messy fringe with texture",
                "textured crop with volume",
                "side part with flow",
                "medium waves",
            ],
            "colors": {
                "warm": ["emerald", "royal blue", "gold", "terracotta"],
                "cool": ["plum", "navy", "silver", "deep purple"],
                "neutral": ["charcoal", "olive", "maroon", "taupe"],
            },
            "outfits": [
                "turtleneck + blazer + slim pants + chelsea boots",
                "structured coat + mock neck + straight denim",
            ],
        },
    }
    
    shape_data = recommendations.get(face_shape, recommendations["oval"])
    undertone_colors = shape_data["colors"].get(undertone, shape_data["colors"]["neutral"])
    
    return {
        "hairstyles": shape_data["hairstyles"],
        "colors": undertone_colors,
        "outfits": shape_data["outfits"],
    }


# ==================== ENDPOINTS ====================

@app.get("/")
async def root():
    return {
        "message": "AUREX AI Service is running",
        "version": MODEL_VERSION,
        "status": "healthy",
    }


@app.get("/version")
async def version_info():
    """
    Informasi versi lengkap dari AI Service dan library yang digunakan.

    Item #4 Prioritas Sedang — AI Model Versioning:
    Melacak versi MediaPipe dan OpenCV untuk memastikan hasil analisis
    tetap konsisten antar deployment. Jika library di-upgrade, model_version
    berubah dan client bisa menyesuaikan ekspektasi.
    """
    return {
        "service_version": MODEL_VERSION,
        "mediapipe_version": MEDIAPIPE_VERSION,
        "opencv_version": OPENCV_VERSION,
        "python_env": {
            "face_mesh_confidence": 0.5,
            "face_detection_model": "full_range",
            "face_detection_confidence": 0.5,
            "max_file_size_mb": MAX_FILE_SIZE // (1024 * 1024),
            "min_image_dimension": MIN_IMAGE_DIMENSION,
            "max_image_dimension": MAX_IMAGE_DIMENSION,
        },
        "timestamp": time.time(),
    }


@app.get("/health")
async def health():
    return {
        "status": "healthy",
        "version": MODEL_VERSION,
        "mediapipe_version": MEDIAPIPE_VERSION,
        "opencv_version": OPENCV_VERSION,
        "timestamp": time.time(),
    }


@app.post("/analyze-face", dependencies=[Depends(verify_api_key)])
async def analyze_face(file: UploadFile = File(...)):
    """
    Analisis wajah dari file gambar yang diupload.
    
    Parameters:
    - file: File gambar (JPEG/PNG, max 10MB, min 200x200 px)
    
    Headers:
    - X-API-Key: API key untuk autentikasi
    
    Returns:
    - face_shape: Bentuk wajah terdeteksi
    - undertone: Skin undertone
    - style_score: Skor gaya (0-100)
    - confidence: Tingkat kepercayaan (0.0-1.0)
    - hairstyles: Rekomendasi hairstyle
    - colors: Palet warna yang cocok
    - outfits: Ide outfit
    - model_version: Versi model AI
    """
    start_time = time.time()
    
    # Validasi input gambar
    img, h, w = await validate_image(file)
    
    # Konversi ke RGB untuk MediaPipe
    img_rgb = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
    
    # Deteksi wajah
    landmarks = detect_face(img_rgb)
    
    if landmarks is None:
        raise HTTPException(
            status_code=400,
            detail="Tidak ada wajah terdeteksi. Pastikan wajah terlihat jelas dan pencahayaan cukup.",
        )
    
    # Analisis
    face_shape, shape_confidence = classify_face_shape(landmarks)
    undertone, undertone_confidence = detect_skin_undertone(img_rgb)
    symmetry = calculate_face_symmetry(landmarks)
    
    # Hitung confidence rata-rata
    avg_confidence = round((shape_confidence + undertone_confidence) / 2, 2)
    
    # Hitung style score (BUKAN random!)
    style_score, scoring_confidence = calculate_style_score(
        face_shape, undertone, symmetry, avg_confidence,
    )
    
    # Generate rekomendasi
    recs = get_recommendations(face_shape, undertone)
    
    processing_time = round(time.time() - start_time, 2)
    
    logger.info(
        f"Analysis complete | shape={face_shape} undertone={undertone} "
        f"score={style_score} confidence={avg_confidence} "
        f"time={processing_time}s size={w}x{h}"
    )
    
    return {
        "face_shape": face_shape,
        "undertone": undertone,
        "style_score": style_score,
        "confidence": scoring_confidence,
        "hairstyles": recs["hairstyles"],
        "colors": recs["colors"],
        "outfits": recs["outfits"],
        "model_version": MODEL_VERSION,
        "library_versions": {
            "mediapipe": MEDIAPIPE_VERSION,
            "opencv": OPENCV_VERSION,
        },
    }


# ==================== MAIN ====================

if __name__ == "__main__":
    port = int(os.environ.get("AI_SERVICE_PORT", 8001))
    logger.info(f"Starting AUREX AI Service v{MODEL_VERSION} on port {port}")
    uvicorn.run(
        "main:app",
        host="0.0.0.0",
        port=port,
        workers=1,
        log_level="info",
    )
