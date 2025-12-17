import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["lat", "lng", "address", "radius"];

    connect() {
        this._onConnect = this._onConnect.bind(this);
        this.element.addEventListener("ux:map:connect", this._onConnect);

        this._abortGeocode = null;
        this.marker = null;
        this.circle = null;

        this._fitTimer = null;

        // CRITICAL: Prevent Leaflet from breaking tom-select dropdowns
        // Add a capture-phase listener that stops Leaflet from handling clicks on dropdowns
        this._protectDropdowns = (e) => {
            const target = e.target;
            if (target.closest('.ts-wrapper') || target.closest('.ts-dropdown') || target.closest('.ts-control')) {
                // Don't let Leaflet or other handlers interfere with tom-select
                e.stopImmediatePropagation();
            }
        };
        document.addEventListener('mousedown', this._protectDropdowns, { capture: true });
        document.addEventListener('click', this._protectDropdowns, { capture: true });

        // react to radius input changes
        if (this.hasRadiusTarget) {
            this._onRadiusInput = () => this._syncCircle({ fit: true });
            this.radiusTarget.addEventListener("input", this._onRadiusInput);
            this.radiusTarget.addEventListener("change", this._onRadiusInput);
        }
    }

    disconnect() {
        this.element.removeEventListener("ux:map:connect", this._onConnect);

        // Remove the dropdown protection listeners
        if (this._protectDropdowns) {
            document.removeEventListener('mousedown', this._protectDropdowns, { capture: true });
            document.removeEventListener('click', this._protectDropdowns, { capture: true });
        }

        if (this._abortGeocode) this._abortGeocode.abort();
        if (this.marker) this.marker.off();
        if (this.circle) this.circle.remove();
        if (this.map) this.map.off("click");

        if (this._fitTimer) clearTimeout(this._fitTimer);

        if (this.hasRadiusTarget && this._onRadiusInput) {
            this.radiusTarget.removeEventListener("input", this._onRadiusInput);
            this.radiusTarget.removeEventListener("change", this._onRadiusInput);
        }
    }

    _onConnect(event) {
        const d = event?.detail ?? {};
        this.map = d.map;
        this.L   = d.L || window.L;

        if (!this.map || !this.L) {
            console.error("UX Map not ready. detail=", d);
            return;
        }

        this.map.on("click", (e) => this._placeMarker(e.latlng));

        // If form already has coords, use them; otherwise wait for click
        const lat = parseFloat(this.latTarget?.value || "NaN");
        const lng = parseFloat(this.lngTarget?.value || "NaN");
        if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
            this._placeMarker({ lat, lng }, { pan: false });
        }
    }

    _placeMarker(latlng, { pan = true } = {}) {
        const icon = this._dotIcon(28, "#EC4E20");

        if (!this.marker) {
            this.marker = this.L.marker(latlng, {
                icon,
                draggable: true,
                keyboard: false,
                autoPan: true,
                riseOnHover: true,
            }).addTo(this.map);

            // While dragging, don't auto-fit (feels horrible)
            this.marker.on("move", () => {
                const p = this.marker.getLatLng();
                this._updateInputs(p.lat, p.lng, { fire: false });
                this._syncCircle({ fit: false });
            });

            // After drag finishes, do fit
            this.marker.on("dragend", () => {
                const p = this.marker.getLatLng();
                this._updateInputs(p.lat, p.lng, { fire: true });
                this._reverseGeocode(p.lat, p.lng);
                this._syncCircle({ fit: true });
            });
        } else {
            this.marker.setLatLng(latlng);
        }

        if (pan) this.map.panTo(latlng);

        this._updateInputs(latlng.lat, latlng.lng, { fire: true });
        this._reverseGeocode(latlng.lat, latlng.lng);

        // Initial placement should fit
        this._syncCircle({ fit: true });
    }

    _syncCircle({ fit = false } = {}) {
        // Only if radius input exists and we have a marker
        if (!this.marker || !this.hasRadiusTarget) return;

        const center = this.marker.getLatLng();
        const km = parseFloat(this.radiusTarget.value);
        const meters = Number.isFinite(km) && km > 0 ? km * 1000 : null;

        // Create/update/remove circle
        if (meters) {
            if (!this.circle) {
                this.circle = this.L.circle(center, {
                    radius: meters,
                    interactive: false,
                }).addTo(this.map);
            } else {
                this.circle.setLatLng(center);
                this.circle.setRadius(meters);
            }

            if (fit) this._fitToCircle();
        } else if (this.circle) {
            this.circle.remove();
            this.circle = null;
        }
    }

    _fitToCircle() {
        if (!this.circle || !this.map) return;

        // Small debounce to avoid jitter during fast input changes
        if (this._fitTimer) clearTimeout(this._fitTimer);

        this._fitTimer = setTimeout(() => {
            if (!this.circle) return;

            this.map.fitBounds(this.circle.getBounds(), {
                padding: [24, 24],
                maxZoom: 14,
            });
        }, 50);
    }

    _updateInputs(lat, lng, { fire = true } = {}) {
        if (this.hasLatTarget) this.latTarget.value = lat.toFixed(6);
        if (this.hasLngTarget) this.lngTarget.value = lng.toFixed(6);
        if (fire) {
            if (this.hasLatTarget) this.latTarget.dispatchEvent(new Event("input", { bubbles: true }));
            if (this.hasLngTarget) this.lngTarget.dispatchEvent(new Event("input", { bubbles: true }));
        }
    }

    async _reverseGeocode(lat, lng) {
        if (!this.hasAddressTarget) return;
        if (this._abortGeocode) this._abortGeocode.abort();
        this._abortGeocode = new AbortController();

        try {
            const u = new URL("https://nominatim.openstreetmap.org/reverse");
            u.searchParams.set("format", "jsonv2");
            u.searchParams.set("lat", String(lat));
            u.searchParams.set("lon", String(lng));
            u.searchParams.set("addressdetails", "0");

            const res = await fetch(u.toString(), {
                headers: { Accept: "application/json", "User-Agent": "yourapp/1.0" },
                signal: this._abortGeocode.signal,
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data?.display_name) {
                this.addressTarget.value = data.display_name;
                this.addressTarget.dispatchEvent(new Event("input", { bubbles: true }));
            }
        } catch { /* ignore */ }
    }

    _dotIcon(size = 28, color = "#EC4E20") {
        const r = Math.floor(size / 2);
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}">
      <circle cx="${r}" cy="${r}" r="${r - 2}" fill="${color}" stroke="white" stroke-width="2"/>
    </svg>`;
        const url = "data:image/svg+xml;charset=UTF-8," + encodeURIComponent(svg);
        return this.L.icon({ iconUrl: url, iconSize: [size, size], iconAnchor: [r, r] });
    }
}
