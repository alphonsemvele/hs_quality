# QualitéDomicile ML Service

Python microservice for Phase 3 / M9 (IA prédictive). Implements the contract
`app/Services/MLServiceClient.php` already expects on the Laravel side:

```
POST /predict           { "type": "...", "payload": {...} } -> { "job_id": "..." }
GET  /predict/{job_id}  -> 202 (running) | 200 { "result": {...} } | 404 | 502 (failed)
```

Auth: `Authorization: Bearer <ML_SERVICE_AUTH_SECRET>`. Must match Laravel's
`ML_SERVICE_SECRET`. Left empty in both, auth is skipped (local dev only).

## Current state: heuristic placeholders, not trained models

The three prediction types (`risque_burnout`, `perte_autonomie`,
`analyse_rapport`) are rule-based scoring functions in `app/predictors/`, not
scikit-learn/transformer models. There's no production outcome data yet to
train on — see each predictor's docstring for the exact rules and the
intended replacement path. The HTTP contract, job lifecycle, and async
submit/poll flow are real and won't need to change when real models replace
the heuristics.

## Run locally

```bash
cd ml-service
python -m venv .venv && source .venv/bin/activate
pip install -r requirements-dev.txt
cp .env.example .env
uvicorn app.main:app --reload --port 8001
```

Point Laravel at it via `.env`:

```
ML_SERVICE_URL=http://localhost:8001
ML_SERVICE_SECRET=
ML_SERVICE_TIMEOUT=10
```

## Test

```bash
pytest
```

## Docker

```bash
docker build -t qualitedomicile-ml-service .
docker run -p 8001:8001 --env-file .env qualitedomicile-ml-service
```
