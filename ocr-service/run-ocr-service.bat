@echo off
setlocal

pushd "%~dp0" >nul 2>&1
if errorlevel 1 (
    echo Failed to switch to the OCR service directory.
    exit /b 1
)

set "PYTHON=f:\conda\envs\gpu-jupyter\python.exe"
set "ENV_ROOT=f:\conda\envs\gpu-jupyter"
set "OCR_PADDLE_DEVICE=gpu:0"
set "OCR_TESSERACT_CMD=C:\Program Files\Tesseract-OCR\tesseract.exe"
set "OCR_STUB_MODELSCOPE=1"
set "OCR_FAST_MODE=1"
set "OCR_MAX_PAGES=0"
set "OCR_PREFER_PDF_TEXT=1"
set "PADDLE_PDX_DISABLE_MODEL_SOURCE_CHECK=True"
set "PATH=%ENV_ROOT%\Lib\site-packages\nvidia\cublas\bin;%ENV_ROOT%\Lib\site-packages\nvidia\cuda_runtime\bin;%ENV_ROOT%\Lib\site-packages\nvidia\cudnn\bin;%ENV_ROOT%\Lib\site-packages\nvidia\cufft\bin;%ENV_ROOT%\Lib\site-packages\nvidia\curand\bin;%ENV_ROOT%\Lib\site-packages\nvidia\cusolver\bin;%ENV_ROOT%\Lib\site-packages\nvidia\cusparse\bin;%ENV_ROOT%\Lib\site-packages\nvidia\nvjitlink\bin;%ENV_ROOT%\Library\bin;%ENV_ROOT%;%ENV_ROOT%\Scripts;%PATH%"

if not exist "%PYTHON%" (
    echo Could not find %PYTHON%.
    popd
    exit /b 1
)

if not exist "%OCR_TESSERACT_CMD%" (
    echo Could not find Tesseract at %OCR_TESSERACT_CMD%.
    popd
    exit /b 1
)

"%PYTHON%" -m pip install -r requirements.txt
if errorlevel 1 (
    popd
    exit /b 1
)

"%PYTHON%" server.py
set "EXIT_CODE=%ERRORLEVEL%"

popd
exit /b %EXIT_CODE%
