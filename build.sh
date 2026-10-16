#!/usr/bin/env bash
# SKYOS ACARS Client - Linux / macOS Build Script
# Builds a single-file executable using PyInstaller.
#
# Linux:  Produces a binary with no extension (run with ./SKYOS_ACARS)
#         Or bundle as .AppImage with additional tooling.
# macOS:  Produces SKYOS_ACARS.app (bundle). Package into .dmg separately.

set -e

echo "========================================"
echo "SKYOS ACARS Client - Build"
echo "========================================"

# Check Python
if ! command -v python3 &> /dev/null; then
    echo "ERROR: Python 3 not found. Install python3 and python3-tk."
    echo "  Ubuntu/Debian: sudo apt install python3 python3-tk python3-pip"
    echo "  Fedora:        sudo dnf install python3 python3-tkinter python3-pip"
    echo "  Arch:          sudo pacman -S python python-tk python-pip"
    exit 1
fi

# Install PyInstaller if missing
if ! python3 -m PyInstaller --version &> /dev/null; then
    echo "Installing PyInstaller..."
    python3 -m pip install --upgrade pip
    python3 -m pip install pyinstaller
fi

# Build
echo "Building SKYOS_ACARS..."
python3 -m PyInstaller --noconfirm --clean skyos_acars_client.spec

echo ""
echo "========================================"
echo "BUILD COMPLETE"
echo "========================================"

if [[ "$OSTYPE" == "darwin"* ]]; then
    echo "Output: dist/SKYOS_ACARS.app"
    echo ""
    echo "macOS: To create a .dmg, use Disk Utility or:"
    echo "  hdiutil create -volname 'SKYOS ACARS' -srcfolder dist/SKYOS_ACARS.app"
    echo "         -ov -format UDZO -o SKYOS_ACARS.dmg"
else
    echo "Output: dist/SKYOS_ACARS"
    echo ""
    echo "Linux: Run with ./dist/SKYOS_ACARS"
    echo "       No Python installation required on the target machine."
    echo ""
    echo "Optional: Package as .AppImage using appimagetool."
fi
