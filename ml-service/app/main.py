import time
import uuid

from fastapi import BackgroundTasks, Depends, FastAPI, HTTPException, Response, status

from app.auth import require_bearer_token
from app.config import Settings, get_settings
from app.predictors.registry import get_predictor
from app.schemas import PredictRequest, PredictResponse, ResultResponse
from app.store import InMemoryJobStore, get_job_store

app = FastAPI(title="QualitéDomicile ML Service", version="0.1.0")


@app.get("/health")
def health() -> dict[str, str]:
    """Unauthenticated — polled by infra health checks, not by Laravel."""
    return {"status": "ok"}


@app.post("/predict", response_model=PredictResponse, dependencies=[Depends(require_bearer_token)])
def submit_prediction(
    request: PredictRequest,
    background_tasks: BackgroundTasks,
    store: InMemoryJobStore = Depends(get_job_store),
) -> PredictResponse:
    job_id = str(uuid.uuid4())
    store.create(job_id)
    background_tasks.add_task(_run_prediction, job_id, request, store)

    return PredictResponse(job_id=job_id)


@app.get(
    "/predict/{job_id}",
    response_model=None,
    dependencies=[Depends(require_bearer_token)],
    responses={202: {"description": "Still processing"}},
)
def get_prediction_result(
    job_id: str,
    store: InMemoryJobStore = Depends(get_job_store),
    settings: Settings = Depends(get_settings),
) -> ResultResponse | Response:
    job = store.get(job_id)
    if job is None:
        raise HTTPException(status.HTTP_404_NOT_FOUND, detail="Unknown job_id.")

    age = time.monotonic() - job.created_at
    if job.status == "running" or age < settings.poll_grace_seconds:
        return Response(status_code=status.HTTP_202_ACCEPTED)

    if job.status == "failed":
        raise HTTPException(status.HTTP_502_BAD_GATEWAY, detail=job.error or "Prediction failed.")

    return ResultResponse(result=job.result or {})


def _run_prediction(job_id: str, request: PredictRequest, store: InMemoryJobStore) -> None:
    try:
        predictor = get_predictor(request.type)
        result = predictor.predict(request.payload)
        store.complete(job_id, result)
    except Exception as exc:  # noqa: BLE001 — boundary: any predictor failure must reach the job store, not crash the worker
        store.fail(job_id, str(exc))
