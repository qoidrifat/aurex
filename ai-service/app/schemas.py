"""Pydantic schemas exposed by the AUREX AI microservice."""

from __future__ import annotations

from pydantic import BaseModel, Field


class HealthResponse(BaseModel):
    status: str
    service: str
    version: str


class AnalyzeResponse(BaseModel):
    face_shape: str = Field(..., examples=["oval"])
    skin_undertone: str = Field(..., examples=["warm"])
    style_score: int = Field(..., ge=0, le=100, examples=[82])
    hairstyles: list[str] = Field(
        default_factory=list,
        examples=[["textured quiff", "mid fade"]],
    )
    colors: list[str] = Field(
        default_factory=list,
        examples=[["olive", "camel", "rust"]],
    )
    outfits: list[str] = Field(
        default_factory=list,
        examples=[["olive tee + black jeans"]],
    )
    meta: dict[str, str] = Field(default_factory=dict)
