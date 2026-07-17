"""Mock face-analysis implementation.

This module intentionally avoids shipping a real ML model in this scaffold.
Instead it hashes the incoming image bytes to produce a deterministic
"style profile" so the end-to-end flow is demonstrable without GPU access.

When you're ready to plug in a real model:

    - Replace `analyze_image` with face-detection + attribute-classification
      calls (MediaPipe Face Mesh, OpenCV DNN, or a fine-tuned CNN).
    - Keep the return type (AnalyzeResponse) the same so the Laravel client
      does not need to change.
"""

from __future__ import annotations

import hashlib
import io
import random

from PIL import Image

from .schemas import AnalyzeResponse

_FACE_SHAPES = ["oval", "round", "square", "heart", "oblong"]
_UNDERTONES = ["warm", "cool", "neutral"]

_HAIRSTYLES = [
    ["textured quiff", "mid fade", "crew cut"],
    ["modern pompadour", "low fade", "side part"],
    ["curtain fringe", "buzz cut", "taper fade"],
    ["messy crop", "undercut", "classic side part"],
]

_PALETTES = [
    ["olive", "camel", "rust", "charcoal"],
    ["navy", "cream", "sand", "forest"],
    ["slate", "oat", "terracotta", "ink"],
    ["charcoal", "cream", "rust", "olive"],
]

_OUTFITS = [
    ["olive tee + black jeans", "cream knit + tailored trousers", "rust overshirt + dark denim"],
    ["charcoal henley + chinos", "camel coat + white tee", "navy blazer + grey trousers"],
    ["olive bomber + cargo pants", "cream oxford + selvedge denim", "rust flannel + black chinos"],
]


def _validate_image(payload: bytes) -> tuple[int, int]:
    try:
        with Image.open(io.BytesIO(payload)) as img:
            img.verify()
        with Image.open(io.BytesIO(payload)) as img:
            return img.size
    except Exception as exc:  # noqa: BLE001 - re-raised as ValueError
        raise ValueError("Uploaded file is not a valid image") from exc


def analyze_image(payload: bytes) -> AnalyzeResponse:
    """Produce a deterministic mock analysis from image bytes."""

    width, height = _validate_image(payload)

    seed_bytes = hashlib.sha256(payload).digest()[:8]
    seed = int.from_bytes(seed_bytes, "big")
    rng = random.Random(seed)

    face_shape = rng.choice(_FACE_SHAPES)
    undertone = rng.choice(_UNDERTONES)
    hairstyles = rng.choice(_HAIRSTYLES)
    palette = rng.choice(_PALETTES)
    outfits = rng.choice(_OUTFITS)
    score = rng.randint(60, 94)

    return AnalyzeResponse(
        face_shape=face_shape,
        skin_undertone=undertone,
        style_score=score,
        hairstyles=list(hairstyles),
        colors=list(palette),
        outfits=list(outfits),
        meta={
            "source": "mock",
            "image_size": f"{width}x{height}",
        },
    )
