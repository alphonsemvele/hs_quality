from app.predictors.autonomy import AutonomyLossPredictor
from app.predictors.base import Predictor
from app.predictors.burnout import BurnoutPredictor
from app.predictors.report_analysis import ReportAnalysisPredictor
from app.schemas import PredictionType

_REGISTRY: dict[PredictionType, Predictor] = {
    PredictionType.RISQUE_BURNOUT: BurnoutPredictor(),
    PredictionType.PERTE_AUTONOMIE: AutonomyLossPredictor(),
    PredictionType.ANALYSE_RAPPORT: ReportAnalysisPredictor(),
}


def get_predictor(prediction_type: PredictionType) -> Predictor:
    return _REGISTRY[prediction_type]
