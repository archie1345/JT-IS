@echo off
setlocal

pushd "%~dp0" >nul 2>&1
if errorlevel 1 (
    echo Failed to switch to the OCR service directory.
    exit /b 1
)

where powershell.exe >nul 2>&1
if errorlevel 1 (
    echo PowerShell is required to run the portable OCR startup script.
    popd
    exit /b 1
)

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0run-ocr-service.ps1"
set "EXIT_CODE=%ERRORLEVEL%"

popd
exit /b %EXIT_CODE%
