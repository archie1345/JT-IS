# JTE OCR Service

Run this service separately from Laravel:

```powershell
.\run-ocr-service.bat
```

The startup script is portable. It loads optional machine-specific settings from
`.env.local`, then uses a Paddle-compatible Python environment. If no configured
environment exists, it creates `ocr-service\.venv-paddle` with Python 3.12, 3.11,
or 3.10 when one is installed, installs PaddleOCR dependencies, and installs the
CPU `paddlepaddle` runtime. Each team member can keep their own paths in
`.env.local` without committing them.

Create a local config only if you need custom paths or GPU:

```powershell
copy .env.local.example .env.local
notepad .env.local
```

For the easiest team setup, install Python 3.12 or 3.11 first, then run:

```powershell
cd ocr-service
.\run-ocr-service.bat
```

The existing `.venv` may use Python 3.14, which does not currently have a
matching PaddlePaddle wheel. The startup script therefore creates
`.venv-paddle` instead of using that unsupported env.

Or run the setup manually with a normal virtual environment:

```bash
cd ocr-service
py -3.12 -m venv .venv-paddle
.venv-paddle\Scripts\activate
pip install -r requirements.txt
pip install paddlepaddle
python server.py
```

Laravel forwards uploads to:

```env
OCR_SERVICE_URL=http://127.0.0.1:8001
OCR_SERVICE_TIMEOUT=180
```

When Laravel runs in Docker and the OCR service runs on Windows, use:

```env
OCR_SERVICE_URL=http://host.docker.internal:8001
```

RAB PDFs can be slow if they are scanned table documents. The startup scripts use these defaults:

```env
OCR_PREFER_PDF_TEXT=0
OCR_FAST_MODE=1
OCR_MAX_PAGES=5
```

`OCR_PREFER_PDF_TEXT=0` uses real OCR for PDFs by default. This is slower, but safer for RAB/contract PDFs that contain broken embedded text layers. Set `OCR_PREFER_PDF_TEXT=1` only for clean digital PDFs where selectable text copies correctly. Increase `OCR_MAX_PAGES` only when you need more pages from scanned PDFs. Set `OCR_MAX_PAGES=0` for no page limit, but large scanned PDFs may exceed Laravel's OCR timeout.

Useful local settings:

```env
# Optional: exact Python executable. Use this for Conda, custom venvs, or
# notebook-created environments that are not inside this repo.
OCR_PYTHON=f:\conda\envs\gpu-jupyter\python.exe

# Optional: environment root. Usually only needed for GPU Conda installs so
# CUDA/Paddle DLL folders can be added to PATH.
OCR_ENV_ROOT=f:\conda\envs\gpu-jupyter

# CPU is easiest for team testing. Use gpu:0 only on machines with working GPU
# PaddleOCR support.
OCR_PADDLE_DEVICE=gpu:0

# Set to 0 only when OCR_PYTHON points to an environment that already has
# paddlepaddle-gpu or another Paddle runtime installed.
OCR_INSTALL_PADDLE=1

# Optional if Tesseract is installed somewhere non-standard.
OCR_TESSERACT_CMD=C:\Program Files\Tesseract-OCR\tesseract.exe
```
