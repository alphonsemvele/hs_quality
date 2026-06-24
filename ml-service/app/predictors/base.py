from typing import Any, Protocol


class Predictor(Protocol):
    """Implemented by every prediction type's handler.

    `payload` is the free-form `input_data` array the Laravel controller
    accepted (see StorePredictionRequest — only `required|array` is
    enforced, no fixed shape), so every predictor must tolerate missing
    keys and fall back to neutral defaults rather than raising.
    """

    def predict(self, payload: dict[str, Any]) -> dict[str, Any]: ...
