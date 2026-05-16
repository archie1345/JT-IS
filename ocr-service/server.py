from __future__ import annotations

import asyncio
import sys

import uvicorn


def configure_windows_event_loop() -> None:
    if sys.platform != "win32":
        return

    selector_policy = getattr(asyncio, "WindowsSelectorEventLoopPolicy", None)

    if selector_policy is not None:
        asyncio.set_event_loop_policy(selector_policy())


if __name__ == "__main__":
    configure_windows_event_loop()
    uvicorn.run("api:app", host="0.0.0.0", port=8001)
