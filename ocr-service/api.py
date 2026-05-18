from __future__ import annotations

import asyncio
import io
import os
import re
import sys
import tempfile
import types
from pathlib import Path
from typing import Any, Iterator

import cv2
import fitz
import numpy as np
from fastapi import FastAPI, File, HTTPException, UploadFile
from PIL import Image

app = FastAPI(title="JTE OCR Service")

PDF_SCALE = 3
os.environ.setdefault("FLAGS_use_mkldnn", "0")

PADDLE_DEVICE_REQUESTED = os.environ.get("OCR_PADDLE_DEVICE", "auto")
PADDLE_ENABLE_MKLDNN = os.environ.get("OCR_PADDLE_ENABLE_MKLDNN", "0") == "1"
DEFAULT_TESSERACT_CMD = Path(r"C:\Program Files\Tesseract-OCR\tesseract.exe")
OCR_FAST_MODE = os.environ.get("OCR_FAST_MODE", "1") == "1"
OCR_MAX_PAGES = max(0, int(os.environ.get("OCR_MAX_PAGES", "0")))
OCR_PREFER_PDF_TEXT = os.environ.get("OCR_PREFER_PDF_TEXT", "1") == "1"
paddle_runtime_error = ""


def resolve_paddle_device(requested_device: str) -> str:
    if requested_device.lower() != "auto":
        return requested_device

    try:
        import paddle

        if paddle.device.is_compiled_with_cuda() and paddle.device.cuda.device_count() > 0:
            return "gpu:0"
    except Exception:
        pass

    return "cpu"


PADDLE_DEVICE = resolve_paddle_device(PADDLE_DEVICE_REQUESTED)


@app.on_event("startup")
async def configure_asyncio_exception_handler() -> None:
    loop = asyncio.get_running_loop()
    original_handler = loop.get_exception_handler()

    def ignore_client_connection_reset(_: asyncio.AbstractEventLoop, context: dict[str, Any]) -> None:
        exception = context.get("exception")

        if isinstance(exception, ConnectionResetError):
            return

        if original_handler is not None:
            original_handler(loop, context)
            return

        loop.default_exception_handler(context)

    loop.set_exception_handler(ignore_client_connection_reset)


if os.environ.get("OCR_STUB_MODELSCOPE", "1") == "1" and "modelscope" not in sys.modules:
    modelscope_stub = types.ModuleType("modelscope")

    def snapshot_download(*args: Any, **kwargs: Any) -> None:
        raise RuntimeError("ModelScope is disabled for this OCR service process.")

    modelscope_stub.snapshot_download = snapshot_download
    sys.modules["modelscope"] = modelscope_stub

try:
    from paddleocr import PaddleOCR

    try:
        paddle_ocr = PaddleOCR(lang="en", device=PADDLE_DEVICE, enable_mkldnn=PADDLE_ENABLE_MKLDNN)
        paddle_device = PADDLE_DEVICE
    except Exception:
        paddle_ocr = PaddleOCR(lang="en", device="cpu", enable_mkldnn=PADDLE_ENABLE_MKLDNN)
        paddle_device = "cpu"
except Exception as paddle_error:
    paddle_ocr = None
    paddle_device = ""
    paddle_init_error = str(paddle_error)
else:
    paddle_init_error = ""

try:
    import pytesseract
    from pytesseract import TesseractNotFoundError

    configured_tesseract = os.environ.get("OCR_TESSERACT_CMD")
    if configured_tesseract:
        pytesseract.pytesseract.tesseract_cmd = configured_tesseract
    elif DEFAULT_TESSERACT_CMD.exists():
        pytesseract.pytesseract.tesseract_cmd = str(DEFAULT_TESSERACT_CMD)
except Exception:
    pytesseract = None
    TesseractNotFoundError = RuntimeError


local_tessdata = Path(__file__).with_name("tessdata")
tesseract_config = f"--tessdata-dir {local_tessdata}" if local_tessdata.exists() else ""


def is_tesseract_available() -> bool:
    if pytesseract is None:
        return False

    try:
        pytesseract.get_tesseract_version()
    except Exception:
        return False

    return True


tesseract_available = is_tesseract_available()


def get_paddle_cuda_info() -> dict[str, Any]:
    try:
        import paddle

        compiled_cuda = bool(paddle.device.is_compiled_with_cuda())
        cuda_count = int(paddle.device.cuda.device_count()) if compiled_cuda else 0

        return {
            "compiled_cuda": compiled_cuda,
            "cuda_device_count": cuda_count,
            "active_device": paddle.device.get_device(),
        }
    except Exception as exc:
        return {
            "compiled_cuda": False,
            "cuda_device_count": 0,
            "active_device": "",
            "error": str(exc),
        }


def get_tesseract_lang() -> str:
    if pytesseract is None or not tesseract_available:
        return ""

    if local_tessdata.exists():
        languages = {path.stem for path in local_tessdata.glob("*.traineddata")}
        preferred = [language for language in ["ind", "eng"] if language in languages]

        return "+".join(preferred) or "eng"

    try:
        languages = set(pytesseract.get_languages(config=tesseract_config))
    except Exception:
        return "eng"

    preferred = [language for language in ["ind", "eng"] if language in languages]

    return "+".join(preferred) or "eng"


tesseract_lang = get_tesseract_lang()


def extract_pdf_text(content: bytes) -> str:
    document = fitz.open(stream=content, filetype="pdf")
    try:
        text = "\n\n".join(page.get_text("text") for page in document)
    finally:
        document.close()

    return normalize_text(text)


def has_useful_text(text: str) -> bool:
    return len(re.findall(r"[A-Za-z0-9]", text)) >= 80


def render_pdf_pages(content: bytes) -> Iterator[tuple[int, np.ndarray]]:
    document = fitz.open(stream=content, filetype="pdf")

    try:
        for page_index, page in enumerate(document):
            if OCR_MAX_PAGES > 0 and page_index >= OCR_MAX_PAGES:
                break

            pixmap = page.get_pixmap(matrix=fitz.Matrix(PDF_SCALE, PDF_SCALE), alpha=False)
            image = Image.open(io.BytesIO(pixmap.tobytes("png"))).convert("RGB")
            yield page_index + 1, np.array(image)
    finally:
        document.close()


def load_image(content: bytes) -> list[tuple[int, np.ndarray]]:
    image = Image.open(io.BytesIO(content)).convert("RGB")

    return [(1, np.array(image))]


def preprocess(image: np.ndarray, mode: str) -> np.ndarray:
    gray = cv2.cvtColor(image, cv2.COLOR_RGB2GRAY)
    gray = cv2.fastNlMeansDenoising(gray, None, 10, 7, 21)
    gray = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8)).apply(gray)

    if mode == "threshold":
        gray = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)[1]
    elif mode == "adaptive":
        gray = cv2.adaptiveThreshold(
            gray,
            255,
            cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
            cv2.THRESH_BINARY,
            35,
            11,
        )

    return cv2.cvtColor(gray, cv2.COLOR_GRAY2RGB)


def rotate(image: np.ndarray, angle: int) -> np.ndarray:
    if angle == 0:
        return image
    if angle == 90:
        return cv2.rotate(image, cv2.ROTATE_90_CLOCKWISE)
    if angle == 180:
        return cv2.rotate(image, cv2.ROTATE_180)
    if angle == 270:
        return cv2.rotate(image, cv2.ROTATE_90_COUNTERCLOCKWISE)

    raise ValueError(f"Unsupported angle: {angle}")


def normalize_text(text: str) -> str:
    replacements = {
        "MUTUALCHECK": "MUTUAL CHECK",
        "PROGRESFISIK": "PROGRES FISIK",
        "PIHAKKESATU": "PIHAK KESATU",
        "PIHAKKEDUA": "PIHAK KEDUA",
        "PIHAKKETIGA": "PIHAK KETIGA",
        "JASATIRTAENERGI": "JASA TIRTA ENERGI",
        "JASATIRTAI": "JASA TIRTA I",
        "PROJECTMANAGER": "PROJECT MANAGER",
        "TEAMLEADER": "TEAM LEADER",
    }

    text = re.sub(r"[^\x09\x0A\x0D\x20-\x7EÀ-ž]", " ", text)
    text = re.sub(r"([0-9])\s+([.,])\s+([0-9])", r"\1\2\3", text)
    text = re.sub(r"\s+([.,:;%])", r"\1", text)
    text = re.sub(r"\s{3,}", "  ", text)

    tokens = text.split()
    short_tokens = [token for token in tokens if re.match(r"^[A-Za-z0-9]{1,2}[.,:;/%-]?$", token)]
    if len(tokens) > 40 and len(short_tokens) / max(len(tokens), 1) > 0.45:
        compact = re.sub(r"\s+", "", text).upper()
        for source, target in replacements.items():
            compact = compact.replace(source, target)
        return compact

    return text.strip()


def correct_document_ocr_errors(text: str) -> str:
    lines: list[str] = []

    for line in text.splitlines():
        line = re.sub(r"\bN[o0]m[o0]r\b", "Nomor", line, flags=re.I)
        line = re.sub(r"\bN[o0]\.\b", "No.", line, flags=re.I)
        line = re.sub(r"\bPr[o0]gres\b", "Progres", line, flags=re.I)
        line = re.sub(r"\bM[uv]tual\s*Check\b", "Mutual Check", line, flags=re.I)

        if re.search(r"\b(?:Nomor|No\.?)\b", line, flags=re.I):
            line = re.sub(r"(?<=\d):(?=\d)", ".", line)
            line = re.sub(r"[!|]+", "/", line)
            line = re.sub(r"\s*/\s*", "/", line)
            line = re.sub(r"/{2,}", "/", line)

        lines.append(line)

    return "\n".join(lines)


def paddle_ocr_image(image: np.ndarray) -> tuple[str, float, list[dict[str, Any]]]:
    global paddle_ocr
    global paddle_runtime_error

    if paddle_ocr is None:
        return "", 0.0, []

    try:
        result = paddle_ocr.predict(image) if hasattr(paddle_ocr, "predict") else paddle_ocr.ocr(image)
    except Exception as exc:
        paddle_runtime_error = str(exc)
        paddle_ocr = None
        return "", 0.0, []

    lines: list[dict[str, Any]] = []

    for block in result or []:
        if not block:
            continue

        if hasattr(block, "keys") and "rec_texts" in block:
            texts = block.get("rec_texts") or []
            scores = block.get("rec_scores") or []
            boxes = block.get("rec_polys") or block.get("dt_polys") or []

            for index, text in enumerate(texts):
                box = boxes[index] if index < len(boxes) else np.array([[0, 0]])
                confidence = scores[index] if index < len(scores) else 0
                x = min(float(point[0]) for point in box)
                y = min(float(point[1]) for point in box)
                lines.append(
                    {
                        "text": str(text),
                        "confidence": float(confidence),
                        "x": x,
                        "y": y,
                    }
                )
            continue

        for line in block:
            box, data = line
            text, confidence = data
            x = min(point[0] for point in box)
            y = min(point[1] for point in box)
            lines.append(
                {
                    "text": text,
                    "confidence": float(confidence),
                    "x": float(x),
                    "y": float(y),
                }
            )

    lines.sort(key=lambda item: (round(item["y"] / 24), item["x"]))
    text = "\n".join(item["text"] for item in lines)
    confidence = float(np.mean([item["confidence"] for item in lines])) if lines else 0.0

    return correct_document_ocr_errors(normalize_text(text)), confidence, lines


def tesseract_ocr_image(image: np.ndarray) -> tuple[str, float, list[dict[str, Any]]]:
    if pytesseract is None or not tesseract_available:
        return "", 0.0, []

    try:
        data = pytesseract.image_to_data(
        image,
        lang=tesseract_lang,
        config=f"{tesseract_config} --oem 3 --psm 6 -c preserve_interword_spaces=1".strip(),
        output_type=pytesseract.Output.DATAFRAME,
    )
    except TesseractNotFoundError:
        return "", 0.0, []

    data = data.dropna(subset=["text"])
    data = data[data["text"].astype(str).str.strip() != ""]

    if data.empty:
        return "", 0.0, []

    lines: list[dict[str, Any]] = []
    for _, group in data.groupby(["block_num", "par_num", "line_num"]):
        group = group.sort_values("left")
        text = " ".join(group["text"].astype(str).tolist())
        confidence_values = group["conf"].replace(-1, np.nan).dropna()
        lines.append(
            {
                "text": text,
                "confidence": float(confidence_values.mean() or 0) / 100,
                "x": float(group["left"].min()),
                "y": float(group["top"].min()),
            }
        )

    lines.sort(key=lambda item: (round(item["y"] / 24), item["x"]))
    text = "\n".join(item["text"] for item in lines)
    confidence = float(np.mean([item["confidence"] for item in lines])) if lines else 0.0

    return correct_document_ocr_errors(normalize_text(text)), confidence, lines


def score_text(text: str, confidence: float) -> float:
    alnum = len(re.findall(r"[A-Za-z0-9]", text))
    keywords = len(
        re.findall(r"BA|MC|SPK|Nomor|Nama|Jabatan|Pekerjaan|Progress|Progres|Kontrak", text, re.I)
    )

    return confidence * 100 + min(alnum / 20, 50) + keywords * 4


def best_ocr_for_page(image: np.ndarray) -> dict[str, Any]:
    candidates: list[dict[str, Any]] = []
    paddle_modes = ["clean"] if OCR_FAST_MODE else ["clean", "threshold", "adaptive"]
    tesseract_angles = [0] if OCR_FAST_MODE else [0, 90, 180, 270]
    tesseract_modes = ["clean"] if OCR_FAST_MODE else ["clean", "threshold", "adaptive"]

    if paddle_ocr is not None:
        for mode in paddle_modes:
            processed = preprocess(image, mode)
            text, confidence, lines = paddle_ocr_image(processed)

            if text:
                candidates.append(
                    {
                        "angle": 0,
                        "engine": "paddle",
                        "mode": mode,
                        "text": text,
                        "confidence": confidence,
                        "lines": lines,
                        "score": score_text(text, confidence),
                    }
                )

    for angle in tesseract_angles:
        rotated = rotate(image, angle)
        for mode in tesseract_modes:
            processed = preprocess(rotated, mode)
            text, confidence, lines = tesseract_ocr_image(processed)

            candidates.append(
                {
                    "angle": angle,
                    "engine": "tesseract",
                    "mode": mode,
                    "text": text,
                    "confidence": confidence,
                    "lines": lines,
                    "score": score_text(text, confidence),
                }
            )

    if not candidates:
        return {
            "angle": 0,
            "engine": "none",
            "mode": "none",
            "text": "",
            "confidence": 0.0,
            "lines": [],
            "score": 0.0,
        }

    return max(candidates, key=lambda item: item["score"])


@app.get("/health")
def health() -> dict[str, Any]:
    return {
        "ok": True,
        "paddle": paddle_ocr is not None,
        "paddle_device_requested": PADDLE_DEVICE_REQUESTED,
        "paddle_device": paddle_device,
        "paddle_cuda": get_paddle_cuda_info(),
        "paddle_enable_mkldnn": PADDLE_ENABLE_MKLDNN,
        "paddle_init_error": paddle_init_error,
        "paddle_runtime_error": paddle_runtime_error,
        "ocr_fast_mode": OCR_FAST_MODE,
        "ocr_max_pages": OCR_MAX_PAGES,
        "ocr_prefer_pdf_text": OCR_PREFER_PDF_TEXT,
        "tesseract": tesseract_available,
        "tesseract_cmd": getattr(pytesseract.pytesseract, "tesseract_cmd", "") if pytesseract else "",
        "tesseract_lang": tesseract_lang,
    }


@app.post("/ocr")
async def ocr(file: UploadFile = File(...)) -> dict[str, Any]:
    content = await file.read()
    suffix = Path(file.filename or "").suffix.lower()

    try:
        if suffix == ".pdf":
            if OCR_PREFER_PDF_TEXT:
                pdf_text = extract_pdf_text(content)

                if has_useful_text(pdf_text):
                    return {
                        "engine": "python-pdf-text",
                        "pages": [],
                        "text": pdf_text,
                    }

            if paddle_ocr is None and not tesseract_available:
                raise HTTPException(
                    status_code=503,
                    detail="No OCR engine is available. Install PaddleOCR or install Tesseract and add it to PATH.",
                )
            pages = render_pdf_pages(content)
        elif suffix in {".png", ".jpg", ".jpeg", ".webp", ".bmp", ".tif", ".tiff"}:
            if paddle_ocr is None and not tesseract_available:
                raise HTTPException(
                    status_code=503,
                    detail="No OCR engine is available. Install PaddleOCR or install Tesseract and add it to PATH.",
                )
            pages = load_image(content)
        elif suffix in {".txt", ".csv"}:
            return {
                "engine": "python-text",
                "pages": [],
                "text": content.decode("utf-8", errors="replace"),
            }
        else:
            raise HTTPException(status_code=422, detail=f"Unsupported file type: {suffix}")
    except HTTPException:
        raise
    except Exception as exc:
        raise HTTPException(status_code=422, detail=f"Unable to load document: {exc}") from exc

    page_results: list[dict[str, Any]] = []

    for page_number, image in pages:
        result = best_ocr_for_page(image)
        page_results.append({"page": page_number, **result})

    return {
        "engine": "python-paddleocr" if paddle_ocr is not None else "python-tesseract",
        "pages": page_results,
        "text": "\n\n".join(page["text"] for page in page_results),
    }
