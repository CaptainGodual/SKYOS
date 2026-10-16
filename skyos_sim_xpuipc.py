"""
skyos_sim_xpuipc.py
XPUIPC client for X-Plane.

XPUIPC exposes X-Plane datarefs via a TCP/IP interface similar to FSUIPC.
Default port is 49001 (configurable in X-Plane).

This module mirrors the FSUIPCClient interface so the ACARS client can
treat both simulators interchangeably.

Common XPUIPC offsets (mapped to X-Plane datarefs):
  0x0250  Latitude  (degrees)
  0x0258  Longitude (degrees)
  0x0260  Altitude  (feet)
  0x02F0  Indicated airspeed (knots)
  0x0330  On ground flag
  0x3D00  Aircraft model string
"""

import socket
import struct
import logging
from typing import Optional, Dict, Any

logger = logging.getLogger("skyos.sim.xpuipc")


class XPUIPCError(Exception):
    pass


class XPUIPCClient:
    """
    Minimal XPUIPC client for X-Plane.
    Connects to XPUIPC running inside X-Plane (localhost:49001 by default).
    """

    DEFAULT_HOST = "127.0.0.1"
    DEFAULT_PORT = 49001

    OFF_LATITUDE = 0x0250
    OFF_LONGITUDE = 0x0258
    OFF_ALTITUDE = 0x0260
    OFF_INDICATED_AIRSPEED = 0x02F0
    OFF_ON_GROUND = 0x0330
    OFF_AIRCRAFT_TITLE = 0x3D00
    OFF_AIRCRAFT_TITLE_SIZE = 256

    def __init__(self, host: str = DEFAULT_HOST, port: int = DEFAULT_PORT, timeout: float = 3.0):
        self.host = host
        self.port = port
        self.timeout = timeout
        self._sock: Optional[socket.socket] = None
        self.connected = False

    def connect(self) -> bool:
        if self.connected and self._sock is not None:
            return True
        try:
            self._sock = socket.create_connection((self.host, self.port), timeout=self.timeout)
            self._sock.settimeout(self.timeout)
            self.connected = True
            logger.info("XPUIPC connected to %s:%d", self.host, self.port)
            return True
        except OSError as exc:
            logger.warning("XPUIPC connection failed: %s", exc)
            self.connected = False
            return False

    def disconnect(self):
        if self._sock is not None:
            try:
                self._sock.close()
            except OSError:
                pass
            self._sock = None
        self.connected = False

    def _read_raw(self, offset: int, size: int) -> bytes:
        if not self.connected or self._sock is None:
            raise XPUIPCError("Not connected to XPUIPC")
        request = struct.pack("<II", offset, size)
        self._sock.sendall(request)
        expected = 1 + size
        buf = b""
        while len(buf) < expected:
            chunk = self._sock.recv(expected - len(buf))
            if not chunk:
                raise XPUIPCError("Connection closed by XPUIPC")
            buf += chunk
        result = buf[0]
        if result != 0:
            raise XPUIPCError(f"XPUIPC read failed with result code {result}")
        return buf[1:1 + size]

    def read_double(self, offset: int) -> float:
        raw = self._read_raw(offset, 8)
        return struct.unpack("<d", raw)[0]

    def read_int32(self, offset: int) -> int:
        raw = self._read_raw(offset, 4)
        return struct.unpack("<i", raw)[0]

    def read_int16(self, offset: int) -> int:
        raw = self._read_raw(offset, 2)
        return struct.unpack("<h", raw)[0]

    def read_int8(self, offset: int) -> int:
        raw = self._read_raw(offset, 1)
        return struct.unpack("<b", raw)[0]

    def read_string(self, offset: int, size: int) -> str:
        raw = self._read_raw(offset, size)
        return raw.split(b"\x00")[0].decode("ascii", errors="replace").strip()

    def get_position(self) -> Optional[Dict[str, float]]:
        try:
            lat = self.read_double(self.OFF_LATITUDE)
            lon = self.read_double(self.OFF_LONGITUDE)
            return {"lat": lat, "lon": lon}
        except XPUIPCError:
            return None

    def get_altitude(self) -> Optional[int]:
        try:
            return self.read_int32(self.OFF_ALTITUDE)
        except XPUIPCError:
            return None

    def get_airspeed(self) -> Optional[int]:
        try:
            return self.read_int16(self.OFF_INDICATED_AIRSPEED)
        except XPUIPCError:
            return None

    def get_on_ground(self) -> bool:
        try:
            return self.read_int8(self.OFF_ON_GROUND) != 0
        except XPUIPCError:
            return False

    def get_aircraft_title(self) -> str:
        try:
            return self.read_string(self.OFF_AIRCRAFT_TITLE, self.OFF_AIRCRAFT_TITLE_SIZE)
        except XPUIPCError:
            return "Unknown Aircraft"

    def get_telemetry(self) -> Dict[str, Any]:
        pos = self.get_position()
        return {
            "connected": self.connected,
            "latitude": pos["lat"] if pos else None,
            "longitude": pos["lon"] if pos else None,
            "altitude_feet": self.get_altitude(),
            "airspeed_knots": self.get_airspeed(),
            "on_ground": self.get_on_ground(),
            "aircraft": self.get_aircraft_title(),
        }
