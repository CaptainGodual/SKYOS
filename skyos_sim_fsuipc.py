"""
skyos_sim_fsuipc.py
FSUIPC7 client for Microsoft Flight Simulator.

FSUIPC7 exposes flight data via a TCP/IP interface on localhost:8002.
This module implements a minimal client that reads the most common
offsets used for ACARS telemetry.

Common FSUIPC offsets used here:
  0x0250  Latitude  (degrees * 65536 * 360 / 2^32)  double
  0x0258  Longitude (degrees * 65536 * 360 / 2^32)  double
  0x0260  Altitude  (feet * 4 / 65536)              int32  (actually 0x0260 is pressure altitude)
  0x02F0  Indicated Airspeed (knots)                int16
  0x0330  Aircraft on ground (0 = airborne, 1 = ground)  int8
  0x03FA  Zulu time seconds                         int16
  0x3D00  Aircraft title string (256 bytes)         char[256]

Protocol (simplified):
  Client connects to 127.0.0.1:8002
  To read offsets: send a request packet, receive response packet.
  Each request: offset(4 bytes), size(4 bytes), data(size bytes)
"""

import socket
import struct
import logging
from typing import Optional, Dict, Any

logger = logging.getLogger("skyos.sim.fsuipc")


class FSUIPCError(Exception):
    pass


class FSUIPCClient:
    """
    Minimal FSUIPC7 client for MSFS.
    Connects to FSUIPC running inside MSFS (localhost:8002 by default).
    """

    DEFAULT_HOST = "127.0.0.1"
    DEFAULT_PORT = 8002

    # Offsets relevant to ACARS telemetry
    OFF_LATITUDE = 0x0250
    OFF_LONGITUDE = 0x0258
    OFF_ALTITUDE = 0x0260  # pressure altitude feet (raw units vary by sim)
    OFF_INDICATED_AIRSPEED = 0x02F0
    OFF_ON_GROUND = 0x0330
    OFF_ZULU_SECONDS = 0x03FA
    OFF_AIRCRAFT_TITLE = 0x3D00
    OFF_AIRCRAFT_TITLE_SIZE = 256

    def __init__(self, host: str = DEFAULT_HOST, port: int = DEFAULT_PORT, timeout: float = 3.0):
        self.host = host
        self.port = port
        self.timeout = timeout
        self._sock: Optional[socket.socket] = None
        self.connected = False

    def connect(self) -> bool:
        """Open the TCP connection to FSUIPC."""
        if self.connected and self._sock is not None:
            return True
        try:
            self._sock = socket.create_connection((self.host, self.port), timeout=self.timeout)
            self._sock.settimeout(self.timeout)
            self.connected = True
            logger.info("FSUIPC connected to %s:%d", self.host, self.port)
            return True
        except OSError as exc:
            logger.warning("FSUIPC connection failed: %s", exc)
            self.connected = False
            return False

    def disconnect(self):
        """Close the TCP connection."""
        if self._sock is not None:
            try:
                self._sock.close()
            except OSError:
                pass
            self._sock = None
        self.connected = False

    def _read_raw(self, offset: int, size: int) -> bytes:
        """
        Read raw bytes from an FSUIPC offset.
        FSUIPC7 request packet: offset(u32), size(u32), then size bytes of data space.
        Response: result(u8), then size bytes.
        """
        if not self.connected or self._sock is None:
            raise FSUIPCError("Not connected to FSUIPC")

        # Build request: offset + size (FSUIPC7 uses 8-byte header for read)
        request = struct.pack("<II", offset, size)
        self._sock.sendall(request)

        # Read response: 1 byte result + size bytes data
        expected = 1 + size
        buf = b""
        while len(buf) < expected:
            chunk = self._sock.recv(expected - len(buf))
            if not chunk:
                raise FSUIPCError("Connection closed by FSUIPC")
            buf += chunk

        result = buf[0]
        if result != 0:
            raise FSUIPCError(f"FSUIPC read failed with result code {result}")

        return buf[1:1 + size]

    def read_double(self, offset: int) -> float:
        raw = self._read_raw(offset, 8)
        return struct.unpack("<d", raw)[0]

    def read_int32(self, offset: int) -> int:
        raw = self._read_raw(offset, 4)
        return struct.unpack("<i", raw)[0]

    def read_uint32(self, offset: int) -> int:
        raw = self._read_raw(offset, 4)
        return struct.unpack("<I", raw)[0]

    def read_int16(self, offset: int) -> int:
        raw = self._read_raw(offset, 2)
        return struct.unpack("<h", raw)[0]

    def read_int8(self, offset: int) -> int:
        raw = self._read_raw(offset, 1)
        return struct.unpack("<b", raw)[0]

    def read_string(self, offset: int, size: int) -> str:
        raw = self._read_raw(offset, size)
        return raw.split(b"\x00")[0].decode("ascii", errors="replace").strip()

    # ------------------------------------------------------------------
    # High-level telemetry
    # ------------------------------------------------------------------
    def get_position(self) -> Optional[Dict[str, float]]:
        try:
            lat = self.read_double(self.OFF_LATITUDE)
            lon = self.read_double(self.OFF_LONGITUDE)
            return {"lat": lat, "lon": lon}
        except FSUIPCError:
            return None

    def get_altitude(self) -> Optional[int]:
        try:
            # FSUIPC reports altitude in feet * 4 / 65536 at 0x0260 for MSFS
            raw = self.read_int32(self.OFF_ALTITUDE)
            return int(raw / 4.0)
        except FSUIPCError:
            return None

    def get_airspeed(self) -> Optional[int]:
        try:
            return self.read_int16(self.OFF_INDICATED_AIRSPEED)
        except FSUIPCError:
            return None

    def get_on_ground(self) -> bool:
        try:
            return self.read_int8(self.OFF_ON_GROUND) != 0
        except FSUIPCError:
            return False

    def get_aircraft_title(self) -> str:
        try:
            return self.read_string(self.OFF_AIRCRAFT_TITLE, self.OFF_AIRCRAFT_TITLE_SIZE)
        except FSUIPCError:
            return "Unknown Aircraft"

    def get_telemetry(self) -> Dict[str, Any]:
        """Return all available telemetry in one dict."""
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
