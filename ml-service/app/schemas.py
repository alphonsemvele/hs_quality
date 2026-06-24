from enum import Enum
from typing import Any

from pydantic import BaseModel, Field


class PredictionType(str, Enum):
    """Mirrors app/Enums/PredictionType.php exactly — values must stay in sync."""

    RISQUE_BURNOUT = "risque_burnout"
    PERTE_AUTONOMIE = "perte_autonomie"
    ANALYSE_RAPPORT = "analyse_rapport"


class PredictRequest(BaseModel):
    type: PredictionType
    payload: dict[str, Any] = Field(default_factory=dict)


class PredictResponse(BaseModel):
    job_id: str


class ResultResponse(BaseModel):
    result: dict[str, Any]
