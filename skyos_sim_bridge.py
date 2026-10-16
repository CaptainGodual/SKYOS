"""
skyos_sim_bridge.py
Unified simulator bridge for the SKYOS ACARS client.

Wraps FSUIPC7 (MSFS) and XPUIPC (X-Plane) behind a common interface so the
ACARS client can read live telemetry and auto-detect flight phases without
pilot manual input.

Flight phase state machine:
  GROUND      -> TAKING_OFF  when airspeed > 60 kts and not on ground
  TAKING_OFF  -> EN_ROUTE    when altitude > 500 ft
  EN_ROUTE    -> LANDING     when altitude < 500 ft and vertical speed < 0
  LANDING     -> GROUND      when on ground
  GROUND      -> PARKED      when airspeed < 5 kts for > 30s
"""

import time
import logging
from typing import Optional, Dict, Any, Callable

try:
    from skyos_sim_fsuipc import FSUIPCClient
except ImportError:  # pragma: no cover - fallback when run as script
    from sim_fsuipc import FSUIPCClient

try:
    from skyos_sim_xpuipc import XPUIPCClient
except ImportError:  # pragma: no cover
    from sim_xpuipc import XPUIPCClient

logger = logging.getLogger("skyos.sim.bridge")

# Phase constants
PHASE_GROUND = "ground"
PHASE_TAKEOFF = "takeoff"
PHASE_EN_ROUTE = "en_route"
PHASE_LANDING = "landing"
PHASE_PARKED = "parked"

# Thresholds
TAKEOFF_SPEED_KTS = 60
CLIMB_ALTITUDE_FT = 500
DESCEND_ALTITUDE_FT = 500
PARKED_SPEED_KTS = 5
PARKED_DURATION_S = 30


class SimBridge:
    def __init__(self):
        self._client = None
        self._sim_type: Optional[str] = None  # "fsuipc" or "xpuipc"
        self._phase = PHASE_GROUND
        self._last_phase_change = time.time()
        self._parked_since: Optional[float] = None
        self._on_phase_change: Optional[Callable[[str, str], None]] = None
        self._telemetry: Dict[str, Any] = {}

    # ------------------------------------------------------------------
    # Connection
    # ------------------------------------------------------------------
    def connect_fsuipc(self, host: str = "127.0.0.1", port: int = 8002) -> bool:
        client = FSUIPCClient(host=host, port=port)
        if client.connect():
            self._client = client
            self._sim_type = "fsuipc"
            self._phase = PHASE_GROUND
            logger.info("Connected to MSFS via FSUIPC7")
            return True
        return False

    def connect_xpuipc(self, host: str = "127.0.0.1", port: int = 49001) -> bool:
        client = XPUIPCClient(host=host, port=port)
        if client.connect():
            self._client = client
            self._sim_type = "xpuipc"
            self._phase = PHASE_GROUND
            logger.info("Connected to X-Plane via XPUIPC")
            return True
        return False

    def disconnect(self):
        if self._client is not None:
            self._client.disconnect()
        self._client = None
        self._sim_type = None
        self._phase = PHASE_GROUND

    @property
    def connected(self) -> bool:
        return self._client is not None and self._client.connected

    @property
    def sim_type(self) -> Optional[str]:
        return self._sim_type

    @property
    def phase(self) -> str:
        return self._phase

    def set_on_phase_change(self, callback: Callable[[str, str], None]):
        self._on_phase_change = callback

    # ------------------------------------------------------------------
    # Telemetry
    # ------------------------------------------------------------------
    def poll(self) -> Dict[str, Any]:
        """Poll the simulator for fresh telemetry and update flight phase."""
        if not self.connected:
            self._telemetry = {"connected": False}
            return self._telemetry

        try:
            tel = self._client.get_telemetry()
        except Exception as exc:  # pragma: no cover - defensive
            logger.warning("Sim poll error: %s", exc)
            self._telemetry = {"connected": False, "error": str(exc)}
            return self._telemetry

        self._telemetry = tel
        self._update_phase(tel)
        return self._telemetry

    def get_telemetry(self) -> Dict[str, Any]:
        return dict(self._telemetry)

    # ------------------------------------------------------------------
    # Flight phase state machine
    # ------------------------------------------------------------------
    def _update_phase(self, tel: Dict[str, Any]):
        new_phase = self._phase
        now = time.time()

        altitude = tel.get("altitude_feet") or 0
        airspeed = tel.get("airspeed_knots") or 0
        on_ground = tel.get("on_ground", False)

        if self._phase == PHASE_GROUND:
            if not on_ground and airspeed > TAKEOFF_SPEED_KTS:
                new_phase = PHASE_TAKEOFF
            elif airspeed < PARKED_SPEED_KTS and on_ground:
                if self._parked_since is None:
                    self._parked_since = now
                elif now - self._parked_since > PARKED_DURATION_S:
                    new_phase = PHASE_PARKED
            else:
                self._parked_since = None

        elif self._phase == PHASE_TAKEOFF:
            if altitude > CLIMB_ALTITUDE_FT:
                new_phase = PHASE_EN_ROUTE

        elif self._phase == PHASE_EN_ROUTE:
            if altitude < DESCEND_ALTITUDE_FT and not on_ground:
                new_phase = PHASE_LANDING

        elif self._phase == PHASE_LANDING:
            if on_ground:
                new_phase = PHASE_GROUND
                self._parked_since = now

        elif self._phase == PHASE_PARKED:
            if airspeed > PARKED_SPEED_KTS or not on_ground:
                new_phase = PHASE_GROUND
                self._parked_since = None

        if new_phase != self._phase:
            old = self._phase
            self._phase = new_phase
            self._last_phase_change = now
            logger.info("Flight phase: %s -> %s", old, new_phase)
            if self._on_phase_change is not None:
                try:
                    self._on_phase_change(old, new_phase)
                except Exception as exc:  # pragma: no cover
                    logger.warning("Phase change callback error: %s", exc)
