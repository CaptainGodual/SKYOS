#!/usr/bin/env python3
"""
SKYOS ACARS Client
Cross-platform desktop client for Windows / Linux / macOS.
Communicates with the SKYOS Crew Center ACARS API and integrates with
MSFS (FSUIPC7) and X-Plane (XPUIPC) for live telemetry and auto ACARS.
"""

import json
import tkinter as tk
from tkinter import ttk, messagebox, simpledialog
import urllib.request
import urllib.error
import threading
import time

try:
    from skyos_sim_bridge import (
        SimBridge,
        PHASE_GROUND,
        PHASE_TAKEOFF,
        PHASE_EN_ROUTE,
        PHASE_LANDING,
        PHASE_PARKED,
    )
    HAS_SIM = True
except ImportError:  # pragma: no cover - fallback when run as single file
    HAS_SIM = False

    class SimBridge:  # type: ignore
        def __init__(self):
            self.connected = False
            self.sim_type = None
            self.phase = "ground"

        def connect_fsuipc(self, *args, **kwargs):
            return False

        def connect_xpuipc(self, *args, **kwargs):
            return False

        def disconnect(self):
            pass

        def poll(self):
            return {"connected": False}

        def get_telemetry(self):
            return {"connected": False}

        def set_on_phase_change(self, callback):
            pass


class ACARSClient:
    API_BASE = "http://127.0.0.1:8000/api"

    def __init__(self, root: tk.Tk):
        self.root = root
        self.root.title("SKYOS ACARS Client")
        self.root.geometry("1000x700")

        self.token = None
        self.pilot = None
        self.flights = []
        self.selected_flight_id = None

        # Simulator bridge
        self.bridge = SimBridge() if HAS_SIM else None
        self._poll_thread = None
        self._poll_running = False
        self._auto_report_enabled = False
        self._last_reported_phase = None

        self._build_login_view()

    # ------------------------------------------------------------------
    # UI construction
    # ------------------------------------------------------------------
    def _clear(self):
        for widget in self.root.winfo_children():
            widget.destroy()

    def _build_login_view(self):
        self._clear()

        frame = ttk.Frame(self.root, padding="40")
        frame.place(relx=0.5, rely=0.5, anchor="center")

        ttk.Label(frame, text="SKYOS ACARS", font=("Segoe UI", 20, "bold")).pack(pady=(0, 5))
        ttk.Label(
            frame,
            text="Aircraft Communication Addressing and Reporting System",
            font=("Segoe UI", 10),
        ).pack(pady=(0, 30))

        ttk.Label(frame, text="API Base URL:").pack(anchor="w")
        self.api_var = tk.StringVar(value=self.API_BASE)
        ttk.Entry(frame, textvariable=self.api_var, width=45).pack(pady=(0, 15), fill="x")

        ttk.Label(frame, text="Email:").pack(anchor="w")
        self.email_var = tk.StringVar()
        ttk.Entry(frame, textvariable=self.email_var, width=45).pack(pady=(0, 15), fill="x")

        ttk.Label(frame, text="Password:").pack(anchor="w")
        self.pass_var = tk.StringVar()
        ttk.Entry(frame, textvariable=self.pass_var, show="*", width=45).pack(pady=(0, 25), fill="x")

        ttk.Button(frame, text="Connect & Login", command=self._do_login, width=30).pack()

        self.status_var = tk.StringVar(value="")
        ttk.Label(frame, textvariable=self.status_var, foreground="red").pack(pady=(15, 0))

    def _build_main_view(self):
        self._clear()

        # Top bar
        top = ttk.Frame(self.root, padding="8")
        top.pack(fill="x")

        self.top_label = ttk.Label(
            top,
            text=f"SKYOS ACARS · Pilot {self.pilot['pilot_id']} · {self.pilot['name']}",
            font=("Segoe UI", 12, "bold"),
        )
        self.top_label.pack(side="left")

        right = ttk.Frame(top)
        right.pack(side="right")
        ttk.Button(right, text="Logout", command=self._logout).pack(side="right", padx="5")
        if self.bridge:
            ttk.Button(right, text="Simulator", command=self._open_sim_dialog).pack(side="right", padx="5")

        # Simulator status strip
        self.sim_strip = ttk.Frame(self.root, padding="4")
        self.sim_strip.pack(fill="x")
        self.sim_status_var = tk.StringVar(value="Simulator: disconnected")
        ttk.Label(
            self.sim_strip,
            textvariable=self.sim_status_var,
            font=("Segoe UI", 9),
            foreground="gray",
        ).pack(side="left")
        self.auto_report_var = tk.BooleanVar(value=False)
        ttk.Checkbutton(
            self.sim_strip,
            text="Auto-report ACARS events",
            variable=self.auto_report_var,
            command=self._toggle_auto_report,
        ).pack(side="right")

        # Notebook (tabs)
        self.notebook = ttk.Notebook(self.root)
        self.notebook.pack(fill="both", expand=True, padx="10", pady="8")

        # Flights tab
        self.flights_frame = ttk.Frame(self.notebook)
        self.notebook.add(self.flights_frame, text="My Flights")
        self._build_flights_tab()

        # Telemetry tab (simulator)
        self.telemetry_frame = ttk.Frame(self.notebook)
        self.notebook.add(self.telemetry_frame, text="Sim Telemetry")
        self._build_telemetry_tab()

        # PIREP tab
        self.pirep_frame = ttk.Frame(self.notebook)
        self.notebook.add(self.pirep_frame, text="File PIREP")
        self._build_pirep_tab()

        self._refresh_flights()
        self._start_sim_poll()

    def _build_flights_tab(self):
        toolbar = ttk.Frame(self.flights_frame, padding="5")
        toolbar.pack(fill="x")
        ttk.Button(toolbar, text="Refresh", command=self._refresh_flights).pack(side="left")

        columns = ("number", "route", "aircraft", "scheduled", "status", "acars")
        self.tree = ttk.Treeview(
            self.flights_frame, columns=columns, show="headings", selectmode="browse"
        )
        self.tree.heading("number", text="Flight")
        self.tree.heading("route", text="Route")
        self.tree.heading("aircraft", text="Aircraft")
        self.tree.heading("scheduled", text="Scheduled")
        self.tree.heading("status", text="Status")
        self.tree.heading("acars", text="ACARS Status")
        self.tree.column("number", width=80)
        self.tree.column("route", width=150)
        self.tree.column("aircraft", width=140)
        self.tree.column("scheduled", width=140)
        self.tree.column("status", width=100)
        self.tree.column("acars", width=110)
        self.tree.pack(fill="both", expand=True, padx="5", pady="5")

        btn_frame = ttk.Frame(self.flights_frame, padding="10")
        btn_frame.pack(fill="x")

        self.btn_takeoff = ttk.Button(
            btn_frame, text="Takeoff", command=lambda: self._report_event("Flight took off"), state="disabled"
        )
        self.btn_takeoff.pack(side="left", padx="5")

        self.btn_position = ttk.Button(
            btn_frame, text="Position Report", command=self._report_event_position, state="disabled"
        )
        self.btn_position.pack(side="left", padx="5")

        self.btn_landing = ttk.Button(
            btn_frame, text="Landing", command=lambda: self._report_event("Flight completed"), state="disabled"
        )
        self.btn_landing.pack(side="left", padx="5")

        self.btn_cancel = ttk.Button(
            btn_frame, text="Cancel Flight", command=lambda: self._report_event("Flight canceled"), state="disabled"
        )
        self.btn_cancel.pack(side="left", padx="5")

        self.tree.bind("<<TreeviewSelect>>", self._on_flight_select)

    def _build_telemetry_tab(self):
        frame = ttk.Frame(self.telemetry_frame, padding="20")
        frame.pack(fill="both", expand=True)

        ttk.Label(frame, text="Live Simulator Telemetry", font=("Segoe UI", 14, "bold")).pack(pady=(0, 20))

        self.tel_vars = {}
        fields = [
            ("Simulator", "sim_type"),
            ("Phase", "phase"),
            ("Connected", "connected"),
            ("Aircraft", "aircraft"),
            ("Altitude (ft)", "altitude_feet"),
            ("Airspeed (kts)", "airspeed_knots"),
            ("On Ground", "on_ground"),
            ("Latitude", "latitude"),
            ("Longitude", "longitude"),
        ]

        grid = ttk.Frame(frame)
        grid.pack()

        for i, (label, key) in enumerate(fields):
            row = i // 3
            col = (i % 3) * 2
            ttk.Label(grid, text=label + ":", width=16, anchor="e").grid(row=row, column=col, sticky="e", padx="6", pady="4")
            var = tk.StringVar(value="—")
            self.tel_vars[key] = var
            ttk.Label(grid, textvariable=var, width=22, anchor="w").grid(row=row, column=col + 1, sticky="w", padx="6", pady="4")

        ttk.Button(frame, text="Refresh Now", command=self._refresh_telemetry_ui).pack(pady="20")

    def _build_pirep_tab(self):
        frame = ttk.Frame(self.pirep_frame, padding="20")
        frame.pack(fill="both", expand=True)

        ttk.Label(frame, text="Manual PIREP Submission", font=("Segoe UI", 14, "bold")).pack(pady=(0, 20))

        form = ttk.Frame(frame)
        form.pack()

        fields = [
            ("Flight Number", "flight_number", "SKY101"),
            ("Departure ICAO", "departure", "KJFK"),
            ("Arrival ICAO", "arrival", "KBOS"),
            ("Block Time (min)", "block_minutes", "60"),
            ("Landing Rate", "landing_rate", ""),
            ("Remarks", "remarks", ""),
        ]

        self.pirep_vars = {}
        for label, key, default in fields:
            row = ttk.Frame(form)
            row.pack(fill="x", pady="6")
            ttk.Label(row, text=label, width=20).pack(side="left")
            var = tk.StringVar(value=default)
            self.pirep_vars[key] = var
            ttk.Entry(row, textvariable=var, width=45).pack(side="left", padx="10")

        ttk.Button(
            frame, text="Submit PIREP", command=self._submit_pirep, width=30
        ).pack(pady="25")

    # ------------------------------------------------------------------
    # Simulator dialog
    # ------------------------------------------------------------------
    def _open_sim_dialog(self):
        if not HAS_SIM:
            messagebox.showinfo("Simulator", "Simulator integration modules not available.")
            return

        dlg = tk.Toplevel(self.root)
        dlg.title("Simulator Connection")
        dlg.transient(self.root)
        dlg.grab_set()
        dlg.geometry("420x340")

        ttk.Label(dlg, text="Connect to Flight Simulator", font=("Segoe UI", 12, "bold")).pack(pady="12")

        nb = ttk.Notebook(dlg)
        nb.pack(fill="both", expand=True, padx="10", pady="8")

        # FSUIPC tab
        fsuipc = ttk.Frame(nb)
        nb.add(fsuipc, text="MSFS (FSUIPC7)")
        ttk.Label(fsuipc, text="Host:").pack(anchor="w", padx="12", pady=(12, 0))
        fsh = tk.StringVar(value="127.0.0.1")
        ttk.Entry(fsuipc, textvariable=fsh, width=30).pack(padx="12", pady="4", fill="x")
        ttk.Label(fsuipc, text="Port:").pack(anchor="w", padx="12", pady=(10, 0))
        fsp = tk.StringVar(value="8002")
        ttk.Entry(fsuipc, textvariable=fsp, width=10).pack(padx="12", pady="4", anchor="w")

        def do_fsuipc():
            if self.bridge.connect_fsuipc(fsh.get(), int(fsp.get() or 8002)):
                self.sim_status_var.set(f"Simulator: FSUIPC7 ({fsh.get()}:{fsp.get()}) connected")
                self.auto_report_var.set(True)
                dlg.destroy()
            else:
                messagebox.showerror("FSUIPC", "Could not connect to FSUIPC7. Is MSFS running with FSUIPC7 installed?")

        ttk.Button(fsuipc, text="Connect", command=do_fsuipc).pack(pady="14")

        # XPUIPC tab
        xp = ttk.Frame(nb)
        nb.add(xp, text="X-Plane (XPUIPC)")
        ttk.Label(xp, text="Host:").pack(anchor="w", padx="12", pady=(12, 0))
        xph = tk.StringVar(value="127.0.0.1")
        ttk.Entry(xp, textvariable=xph, width=30).pack(padx="12", pady="4", fill="x")
        ttk.Label(xp, text="Port:").pack(anchor="w", padx="12", pady=(10, 0))
        xpp = tk.StringVar(value="49001")
        ttk.Entry(xp, textvariable=xpp, width=10).pack(padx="12", pady="4", anchor="w")

        def do_xpuipc():
            if self.bridge.connect_xpuipc(xph.get(), int(xpp.get() or 49001)):
                self.sim_status_var.set(f"Simulator: XPUIPC ({xph.get()}:{xpp.get()}) connected")
                self.auto_report_var.set(True)
                dlg.destroy()
            else:
                messagebox.showerror("XPUIPC", "Could not connect to XPUIPC. Is X-Plane running with XPUIPC installed?")

        ttk.Button(xp, text="Connect", command=do_xpuipc).pack(pady="14")

        # Disconnect button
        ttk.Button(dlg, text="Disconnect Simulator", command=self._disconnect_sim).pack(pady="10")
        ttk.Button(dlg, text="Close", command=dlg.destroy).pack(pady="4")

    def _disconnect_sim(self):
        if self.bridge:
            self.bridge.disconnect()
        self.sim_status_var.set("Simulator: disconnected")
        self.auto_report_var.set(False)

    def _toggle_auto_report(self):
        self._auto_report_enabled = bool(self.auto_report_var.get())

    # ------------------------------------------------------------------
    # Simulator polling thread
    # ------------------------------------------------------------------
    def _start_sim_poll(self):
        if not self.bridge or HAS_SIM is False:
            return
        self._poll_running = True

        def on_phase_change(old, new):
            self.root.after(0, lambda: self._on_sim_phase_change(old, new))

        self.bridge.set_on_phase_change(on_phase_change)

        def loop():
            while self._poll_running:
                try:
                    self.bridge.poll()
                    self.root.after(0, self._refresh_telemetry_ui)
                    if self._auto_report_enabled:
                        self.root.after(0, self._check_auto_report)
                except Exception:  # pragma: no cover - keep poller alive
                    pass
                time.sleep(1.0)

        self._poll_thread = threading.Thread(target=loop, daemon=True)
        self._poll_thread.start()

    def _refresh_telemetry_ui(self):
        if not self.bridge:
            return
        tel = self.bridge.get_telemetry()
        sim = tel.get("sim_type") or "—"
        self.tel_vars["sim_type"].set(sim.upper() if sim != "—" else sim)
        self.tel_vars["phase"].set(tel.get("phase", "—"))
        self.tel_vars["connected"].set("Yes" if tel.get("connected") else "No")
        self.tel_vars["aircraft"].set(tel.get("aircraft", "—"))
        alt = tel.get("altitude_feet")
        self.tel_vars["altitude_feet"].set(f"{alt:,}" if alt is not None else "—")
        spd = tel.get("airspeed_knots")
        self.tel_vars["airspeed_knots"].set(f"{spd} kts" if spd is not None else "—")
        self.tel_vars["on_ground"].set("Yes" if tel.get("on_ground") else "No")
        lat = tel.get("latitude")
        lon = tel.get("longitude")
        self.tel_vars["latitude"].set(f"{lat:.6f}" if lat is not None else "—")
        self.tel_vars["longitude"].set(f"{lon:.6f}" if lon is not None else "—")

    def _on_sim_phase_change(self, old: str, new: str):
        self._refresh_telemetry_ui()
        if not self._auto_report_enabled:
            return
        flight_id = self.selected_flight_id
        if flight_id is None:
            return
        mapping = {
            PHASE_TAKEOFF: "Flight took off",
            PHASE_LANDING: "Flight completed",
        }
        event = mapping.get(new)
        if event and new != self._last_reported_phase:
            self._last_reported_phase = new
            self._report_event(event, flight_id=flight_id, silent=True)

    def _check_auto_report(self):
        """Auto-submit PIREP on landing if auto-report is enabled."""
        if not self._auto_report_enabled or self.bridge is None:
            return
        tel = self.bridge.get_telemetry()
        if tel.get("phase") == PHASE_LANDING and self._last_reported_phase == PHASE_LANDING:
            flight_id = self.selected_flight_id
            if flight_id is not None:
                self._auto_submit_pirep(flight_id)

    def _auto_submit_pirep(self, flight_id: int):
        flight = next((f for f in self.flights if f["id"] == flight_id), None)
        if not flight:
            return
        tel = self.bridge.get_telemetry()
        data = {
            "flight_number": flight["flight_number"],
            "departure": flight["departure"],
            "arrival": flight["arrival"],
            "block_minutes": flight.get("estimated_minutes", 60),
            "landing_rate": tel.get("airspeed_knots", 0) or 0,
            "remarks": "Auto-submitted via ACARS client (simulator telemetry).",
        }
        result = self._api_request("POST", "/acars/pireps", data)
        if result.get("_error"):
            self.root.after(0, lambda: messagebox.showerror("PIREP", f"Auto PIREP failed: {result.get('_message')}"))
        else:
            self.root.after(0, lambda: messagebox.showinfo("PIREP", "PIREP auto-submitted on landing."))
            self.root.after(0, self._refresh_flights)

    # ------------------------------------------------------------------
    # API helpers
    # ------------------------------------------------------------------
    def _api_request(self, method: str, path: str, data: dict = None, token: bool = True):
        url = f"{self.api_var.get()}{path}"
        headers = {"Content-Type": "application/json"}
        if token and self.token:
            headers["Authorization"] = f"Bearer {self.token}"

        body = None
        if data is not None:
            body = json.dumps(data).encode("utf-8")

        req = urllib.request.Request(url, data=body, headers=headers, method=method)

        try:
            with urllib.request.urlopen(req, timeout=10) as resp:
                raw = resp.read().decode("utf-8")
                return json.loads(raw) if raw else {}
        except urllib.error.HTTPError as e:
            raw = e.read().decode("utf-8")
            try:
                return {"_error": True, "_status": e.code, "_message": json.loads(raw).get("message", str(e))}
            except json.JSONDecodeError:
                return {"_error": True, "_status": e.code, "_message": str(e)}
        except Exception as e:
            return {"_error": True, "_message": str(e)}

    # ------------------------------------------------------------------
    # Actions
    # ------------------------------------------------------------------
    def _do_login(self):
        self.status_var.set("Connecting...")
        self.root.update_idletasks()

        result = self._api_request(
            "POST",
            "/acars/login",
            {"email": self.email_var.get(), "password": self.pass_var.get()},
            token=False,
        )

        if result.get("_error"):
            self.status_var.set(f"Login failed: {result.get('_message')}")
            return

        self.token = result["token"]
        self.pilot = result["pilot"]
        self._build_main_view()

    def _logout(self):
        if self._poll_running:
            self._poll_running = False
            if self._poll_thread:
                self._poll_thread.join(timeout=2)
        if self.bridge:
            self.bridge.disconnect()
        self.token = None
        self.pilot = None
        self.flights = []
        self.selected_flight_id = None
        self._build_login_view()

    def _refresh_flights(self):
        result = self._api_request("GET", "/acars/flights")

        if result.get("_error"):
            messagebox.showerror("Error", f"Failed to load flights: {result.get('_message')}")
            return

        self.flights = result.get("flights", [])
        self._populate_tree()

    def _populate_tree(self):
        for item in self.tree.get_children():
            self.tree.delete(item)

        for f in self.flights:
            route = f"{f.get('departure', '')} → {f.get('arrival', '')}"
            scheduled = f.get("scheduled_at", "")
            if scheduled:
                scheduled = scheduled.replace("T", " ")[:16]
            self.tree.insert(
                "",
                "end",
                iid=str(f["id"]),
                values=(
                    f.get("flight_number", ""),
                    route,
                    f.get("aircraft", ""),
                    scheduled,
                    f.get("status", ""),
                    f.get("acars_status", ""),
                ),
            )

    def _on_flight_select(self, event):
        selection = self.tree.selection()
        has_selection = bool(selection)
        state = "normal" if has_selection else "disabled"
        self.btn_takeoff.config(state=state)
        self.btn_position.config(state=state)
        self.btn_landing.config(state=state)
        self.btn_cancel.config(state=state)
        self.selected_flight_id = int(selection[0]) if selection else None

    def _get_selected_flight_id(self):
        return self.selected_flight_id

    def _report_event(self, event_type: str, flight_id: int = None, silent: bool = False):
        fid = flight_id if flight_id is not None else self._get_selected_flight_id()
        if fid is None:
            if not silent:
                messagebox.showwarning("No flight", "Please select a flight first.")
            return

        result = self._api_request(
            "POST",
            f"/acars/flights/{fid}/event",
            {"event_type": event_type},
        )

        if result.get("_error"):
            if not silent:
                messagebox.showerror("Error", f"Failed to report event: {result.get('_message')}")
            return

        if not silent:
            messagebox.showinfo("ACARS", f"{event_type} reported successfully.")
        self._refresh_flights()

    def _report_event_position(self):
        fid = self._get_selected_flight_id()
        if fid is None:
            messagebox.showwarning("No flight", "Please select a flight first.")
            return

        tel = self.bridge.get_telemetry() if self.bridge else {}
        altitude = tel.get("altitude_feet")
        airspeed = tel.get("airspeed_knots")

        payload = {"event_type": "Flight took off"}
        if altitude is not None:
            payload["altitude"] = int(altitude)
        if airspeed is not None:
            payload["ground_speed"] = int(airspeed)

        result = self._api_request(
            "POST",
            f"/acars/flights/{fid}/event",
            payload,
        )

        if result.get("_error"):
            messagebox.showerror("Error", f"Failed to send position: {result.get('_message')}")
            return

        messagebox.showinfo("ACARS", "Position report sent.")
        self._refresh_flights()

    def _submit_pirep(self):
        data = {key: var.get().strip() for key, var in self.pirep_vars.items()}

        if not data["flight_number"] or not data["departure"] or not data["arrival"]:
            messagebox.showwarning("Missing fields", "Flight number, departure, and arrival are required.")
            return

        try:
            data["block_minutes"] = int(data["block_minutes"])
        except (ValueError, TypeError):
            messagebox.showwarning("Invalid input", "Block time must be a number.")
            return

        if data["landing_rate"]:
            try:
                data["landing_rate"] = int(data["landing_rate"])
            except (ValueError, TypeError):
                data["landing_rate"] = None

        result = self._api_request("POST", "/acars/pireps", data)

        if result.get("_error"):
            messagebox.showerror("Error", f"Failed to submit PIREP: {result.get('_message')}")
            return

        messagebox.showinfo("PIREP", "PIREP submitted for review.")
        for var in self.pirep_vars.values():
            var.set("")


def main():
    root = tk.Tk()
    try:
        root.iconbitmap(default="")
    except Exception:
        pass
    ACARSClient(root)
    root.mainloop()


if __name__ == "__main__":
    main()
