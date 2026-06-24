from functools import lru_cache

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    """Mirrors config/services.php's `ml_service` block on the Laravel side.

    `auth_secret` must match Laravel's ML_SERVICE_SECRET. When left empty
    (local dev default), bearer auth is not enforced — see app/auth.py.
    """

    model_config = SettingsConfigDict(env_prefix="ML_SERVICE_", env_file=".env")

    auth_secret: str = ""
    port: int = 8001
    poll_grace_seconds: float = 2.0
    """Jobs younger than this always report 202 (running), so the demo
    heuristics don't resolve before Laravel's first poll — mirrors a real
    model's inference latency without needing a sleep in the request path."""


@lru_cache
def get_settings() -> Settings:
    return Settings()
