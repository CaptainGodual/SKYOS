@echo off
REM SKYOS ACARS Client - Windows Build Script
REM Builds a single-file .exe for Windows using PyInstaller.

echo ========================================
echo SKYOS ACARS Client - Windows Build
echo ========================================

REM Check Python
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Python not found. Install Python 3.9+ from https://python.org
    pause
    exit /b 1
)

REM Install PyInstaller if missing
python -m PyInstaller --version >nul 2>&1
if %errorlevel% neq 0 (
    echo Installing PyInstaller...
    python -m pip install --upgrade pip
    python -m pip install pyinstaller
    if %errorlevel% neq 0 (
        echo ERROR: Failed to install PyInstaller
        pause
        exit /b 1
    )
)

REM Build the EXE
echo Building SKYOS_ACARS.exe...
python -m PyInstaller --noconfirm --clean skyos_acars_client.spec

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo BUILD COMPLETE
    echo ========================================
    echo Output: dist\SKYOS_ACARS.exe
    echo.
    echo You can now distribute dist\SKYOS_ACARS.exe to Windows users.
    echo No Python installation required on the target machine.
    echo.
    echo Optional: Create an installer with Inno Setup or NSIS.
) else (
    echo ERROR: Build failed
    pause
    exit /b 1
)

pause
