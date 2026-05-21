$ErrorActionPreference = "Stop"

Set-Location $PSScriptRoot

function Import-LocalEnv {
    param([string] $Path)

    if (-not (Test-Path $Path)) {
        return
    }

    Get-Content $Path | ForEach-Object {
        $line = $_.Trim()
        if ($line -eq "" -or $line.StartsWith("#") -or -not $line.Contains("=")) {
            return
        }

        $name, $value = $line.Split("=", 2)
        $name = $name.Trim()
        $value = $value.Trim().Trim('"').Trim("'")

        if ($name) {
            Set-Item -Path "Env:$name" -Value $value
        }
    }
}

function Resolve-Python {
    if ($env:OCR_PYTHON -and (Test-Path $env:OCR_PYTHON)) {
        return $env:OCR_PYTHON
    }

    if ($env:PYTHON -and (Test-Path $env:PYTHON)) {
        return $env:PYTHON
    }

    $managedVenvPython = Join-Path $PSScriptRoot ".venv-paddle\Scripts\python.exe"
    if (Test-Path $managedVenvPython) {
        return $managedVenvPython
    }

    $venvPython = Join-Path $PSScriptRoot ".venv\Scripts\python.exe"
    if ((Test-Path $venvPython) -and (Test-PythonVersionForPaddle $venvPython)) {
        return $venvPython
    }

    $created = New-PaddleVenv
    if ($created) {
        return $created
    }

    if (Test-PythonVersionForPaddle "python") {
        return "python"
    }

    throw @"
No Paddle-compatible Python was found.

PaddleOCR needs a Python version with PaddlePaddle wheels. Install Python 3.12,
3.11, or 3.10, then rerun this script. If you already have a Conda/notebook
environment with PaddleOCR working, set OCR_PYTHON and OCR_ENV_ROOT in
ocr-service\.env.local.
"@
}

function Test-PythonVersionForPaddle {
    param([string] $Python)

    try {
        & $Python -c "import sys; raise SystemExit(0 if sys.version_info[:2] >= (3, 8) and sys.version_info[:2] <= (3, 12) else 1)" *> $null
        return $LASTEXITCODE -eq 0
    } catch {
        return $false
    }
}

function Test-PythonImport {
    param(
        [string] $Python,
        [string] $Module
    )

    try {
        & $Python -c "import $Module" *> $null
        return $LASTEXITCODE -eq 0
    } catch {
        return $false
    }
}

function New-PaddleVenv {
    $target = Join-Path $PSScriptRoot ".venv-paddle"
    $targetPython = Join-Path $target "Scripts\python.exe"

    $candidates = @()

    if ($env:OCR_BOOTSTRAP_PYTHON -and (Test-Path $env:OCR_BOOTSTRAP_PYTHON)) {
        $candidates += ,@($env:OCR_BOOTSTRAP_PYTHON)
    }

    $candidates += ,@("py", "-3.12")
    $candidates += ,@("py", "-3.11")
    $candidates += ,@("py", "-3.10")
    $candidates += ,@("python")

    foreach ($candidate in $candidates) {
        $command = $candidate[0]
        $arguments = @()

        if ($candidate.Count -gt 1) {
            $arguments = $candidate[1..($candidate.Count - 1)]
        }

        try {
            & $command @arguments -c "import sys; raise SystemExit(0 if sys.version_info[:2] >= (3, 8) and sys.version_info[:2] <= (3, 12) else 1)" *> $null
            if ($LASTEXITCODE -ne 0) {
                continue
            }

            Write-Host "Creating PaddleOCR virtual environment at $target"
            & $command @arguments -m venv $target
            if ($LASTEXITCODE -ne 0) {
                continue
            }

            if (Test-Path $targetPython) {
                return $targetPython
            }
        } catch {
            continue
        }
    }

    return $null
}

Import-LocalEnv (Join-Path $PSScriptRoot ".env.local")

if (-not $env:OCR_STUB_MODELSCOPE) { $env:OCR_STUB_MODELSCOPE = "1" }
if (-not $env:OCR_FAST_MODE) { $env:OCR_FAST_MODE = "1" }
if (-not $env:OCR_MAX_PAGES) { $env:OCR_MAX_PAGES = "0" }
if (-not $env:OCR_PREFER_PDF_TEXT) { $env:OCR_PREFER_PDF_TEXT = "1" }
if (-not $env:OCR_PADDLE_DEVICE) { $env:OCR_PADDLE_DEVICE = "auto" }
if (-not $env:OCR_PADDLE_ENABLE_MKLDNN) { $env:OCR_PADDLE_ENABLE_MKLDNN = "0" }
if (-not $env:FLAGS_use_mkldnn) { $env:FLAGS_use_mkldnn = "0" }
if (-not $env:PADDLE_PDX_DISABLE_MODEL_SOURCE_CHECK) { $env:PADDLE_PDX_DISABLE_MODEL_SOURCE_CHECK = "True" }

$defaultTesseract = "C:\Program Files\Tesseract-OCR\tesseract.exe"
if (-not $env:OCR_TESSERACT_CMD -and (Test-Path $defaultTesseract)) {
    $env:OCR_TESSERACT_CMD = $defaultTesseract
}

$python = Resolve-Python
if ((-not (Test-PythonVersionForPaddle $python)) -and (-not (Test-PythonImport $python "paddle"))) {
    throw "Selected Python is not compatible with PaddlePaddle wheels. Use Python 3.12, 3.11, or 3.10, or set OCR_PYTHON to an environment that already has Paddle installed."
}

$envRoot = $env:OCR_ENV_ROOT
if (-not $envRoot -and $python -ne "python") {
    $pythonPath = Resolve-Path $python
    $pythonDir = Split-Path $pythonPath
    $envRoot = if ((Split-Path $pythonDir -Leaf) -eq "Scripts") { Split-Path $pythonDir } else { $pythonDir }
}

if ($envRoot -and (Test-Path $envRoot)) {
    $nvidiaRoot = Join-Path $envRoot "Lib\site-packages\nvidia"
    $nvidiaBins = Get-ChildItem $nvidiaRoot -Directory -ErrorAction SilentlyContinue |
        ForEach-Object { Join-Path $_.FullName "bin" } |
        Where-Object { Test-Path $_ }

    $pathParts = $nvidiaBins + @(
        (Join-Path $envRoot "Library\bin"),
        $envRoot,
        (Join-Path $envRoot "Scripts")
    ) | Where-Object { Test-Path $_ }

    if ($pathParts.Count -gt 0) {
        $env:PATH = ($pathParts -join ";") + ";$env:PATH"
    }
}

& $python -m pip install -r requirements.txt
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}

if ((-not (Test-PythonImport $python "paddle")) -and $env:OCR_INSTALL_PADDLE -ne "0") {
    if (-not (Test-PythonVersionForPaddle $python)) {
        throw "Selected Python cannot install PaddlePaddle. Use Python 3.12, 3.11, or 3.10, or set OCR_PYTHON to a working Conda environment."
    }

    Write-Host "Installing PaddlePaddle CPU runtime for PaddleOCR..."
    & $python -m pip install paddlepaddle
    if ($LASTEXITCODE -ne 0) {
        Write-Error "Unable to install paddlepaddle. Install Python 3.12/3.11/3.10 or configure OCR_PYTHON to a working PaddleOCR environment."
        exit $LASTEXITCODE
    }
}

& $python server.py
exit $LASTEXITCODE
