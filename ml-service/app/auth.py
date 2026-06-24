from fastapi import Depends, HTTPException, status
from fastapi.security import HTTPAuthorizationCredentials, HTTPBearer

from app.config import Settings, get_settings

_bearer = HTTPBearer(auto_error=False)


def require_bearer_token(
    credentials: HTTPAuthorizationCredentials | None = Depends(_bearer),
    settings: Settings = Depends(get_settings),
) -> None:
    """Validates the Bearer token against ML_SERVICE_SECRET.

    If the secret is unset (local dev), auth is skipped entirely — matches
    Laravel's `MLServiceClient::secret()` sending an empty token in that
    case, which would otherwise always 401.
    """
    if settings.auth_secret == "":
        return

    if credentials is None or credentials.credentials != settings.auth_secret:
        raise HTTPException(status.HTTP_401_UNAUTHORIZED, detail="Invalid or missing bearer token.")
