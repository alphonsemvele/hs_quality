import threading
import time
from dataclasses import dataclass, field
from typing import Any, Literal

JobStatus = Literal["running", "done", "failed"]


@dataclass
class Job:
    status: JobStatus
    created_at: float
    result: dict[str, Any] | None = None
    error: str | None = None


class InMemoryJobStore:
    """Single-process job store.

    Sufficient for one ML-service instance. If this service is ever scaled
    to multiple workers/processes, swap this for a Redis-backed store
    (same interface) — nothing in main.py needs to change beyond the
    constructor call.
    """

    def __init__(self) -> None:
        self._jobs: dict[str, Job] = {}
        self._lock = threading.Lock()

    def create(self, job_id: str) -> None:
        with self._lock:
            self._jobs[job_id] = Job(status="running", created_at=time.monotonic())

    def complete(self, job_id: str, result: dict[str, Any]) -> None:
        with self._lock:
            job = self._jobs.get(job_id)
            if job is not None:
                job.status = "done"
                job.result = result

    def fail(self, job_id: str, error: str) -> None:
        with self._lock:
            job = self._jobs.get(job_id)
            if job is not None:
                job.status = "failed"
                job.error = error

    def get(self, job_id: str) -> Job | None:
        with self._lock:
            return self._jobs.get(job_id)


_store = InMemoryJobStore()


def get_job_store() -> InMemoryJobStore:
    return _store
