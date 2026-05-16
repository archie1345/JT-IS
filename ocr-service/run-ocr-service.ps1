$ErrorActionPreference = "Stop"

Set-Location $PSScriptRoot

$python = "f:\conda\envs\gpu-jupyter\python.exe"
$envRoot = "f:\conda\envs\gpu-jupyter"
$env:OCR_PADDLE_DEVICE = "gpu:0"
$env:OCR_TESSERACT_CMD = "C:\Program Files\Tesseract-OCR\tesseract.exe"
$env:OCR_STUB_MODELSCOPE = "1"
$env:OCR_FAST_MODE = "1"
$env:OCR_MAX_PAGES = "5"
$env:OCR_PREFER_PDF_TEXT = "1"
$env:PADDLE_PDX_DISABLE_MODEL_SOURCE_CHECK = "True"

$nvidiaBins = Get-ChildItem "$envRoot\Lib\site-packages\nvidia" -Directory -ErrorAction SilentlyContinue |
    ForEach-Object { Join-Path $_.FullName "bin" } |
    Where-Object { Test-Path $_ }
$env:PATH = (($nvidiaBins + @("$envRoot\Library\bin", $envRoot, "$envRoot\Scripts")) -join ";") + ";$env:PATH"

if (-not (Test-Path $python)) {
    throw "Could not find $python."
}

if (-not (Test-Path $env:OCR_TESSERACT_CMD)) {
    throw "Could not find Tesseract at $env:OCR_TESSERACT_CMD."
}

& $python -m pip install -r requirements.txt
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}

& $python server.py
exit $LASTEXITCODE
