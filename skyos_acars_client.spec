# -*- mode: python -*-

import sys
import os

block_cipher = None

# Ensure the simulator modules are found at build time
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

a = Analysis(
    ['skyos_acars_client.py'],
    pathex=[os.path.dirname(os.path.abspath(__file__))],
    binaries=[],
    datas=[('skyos_sim_fsuipc.py', '.'), ('skyos_sim_xpuipc.py', '.'), ('skyos_sim_bridge.py', '.')],
    hiddenimports=['skyos_sim_fsuipc', 'skyos_sim_xpuipc', 'skyos_sim_bridge'],
    hookspath=[],
    hooksconfig={},
    runtime_hooks=[],
    excludes=[],
    win_no_prefer_redirects=False,
    win_private_assemblies=False,
    cipher=block_cipher,
    noarchive=False,
)

pyz = PYZ(a.pure, a.zipped_data, cipher=block_cipher)

exe = EXE(
    pyz,
    a.scripts,
    a.binaries,
    a.datas,
    [],
    name='SKYOS_ACARS',
    debug=False,
    bootloader_ignore_signals=False,
    strip=False,
    upx=True,
    upx_exclude=[],
    runtime_tmpdir=None,
    console=False,          # No console window on Windows (GUI app)
    disable_windowed_traceback=False,
    argv_emulation=False,
    target_arch=None,
    codesign_identity=None,
    entitlements_file=None,
)
