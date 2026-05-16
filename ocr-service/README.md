# JTE OCR Service

Run this service separately from Laravel:

```powershell
.\run-ocr-service.bat
```

Or run the setup manually:

```bash
cd ocr-service
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
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
OCR_PREFER_PDF_TEXT=1
OCR_FAST_MODE=1
OCR_MAX_PAGES=5
```

`OCR_PREFER_PDF_TEXT=1` extracts selectable PDF text before OCR, which is much faster for digital RAB files. Increase `OCR_MAX_PAGES` only when you need more pages from scanned PDFs. Set `OCR_MAX_PAGES=0` for no page limit, but large scanned PDFs may exceed Laravel's OCR timeout.
