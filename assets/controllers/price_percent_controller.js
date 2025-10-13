import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input'];
    static values = {
        roundTo: { type: Number, default: 1 } // e.g. 100 to round to 1 CZK if value is cents
    }

    reset(event) {
        event.preventDefault();
        if (!this.hasInputTarget) return;

        let price = event.params.price;
        if (typeof price === 'string') {
            price = price.replace(/[^\d.-]/g, '');
        }
        const next = Number(price) || 0;

        this.inputTarget.value = String(next);
        this._notify();
    }

    addPercentage(event) {
        event.preventDefault();
        if (!this.hasInputTarget) return;

        const pct = Number(event.params.pct || 0); // e.g. 10
        const current = Number(this.inputTarget.value || 0);

        let delta = (current * pct) / 100;
        const step = this.roundToValue || 1;
        if (step > 1) {
            delta = Math.round(delta / step) * step;
        } else {
            delta = Math.round(delta);
        }

        const next = current + delta;
        this.inputTarget.value = String(next);
        this._notify();
    }

    // optional: subtract percentage
    minusPct(event) {
        event.preventDefault();
        if (!this.hasInputTarget) return;

        const pct = Number(event.params.pct || 0);
        const current = Number(this.inputTarget.value || 0);

        let delta = (current * pct) / 100;
        const step = this.roundToValue || 1;
        if (step > 1) {
            delta = Math.round(delta / step) * step;
        } else {
            delta = Math.round(delta);
        }

        const next = Math.max(0, current - delta);
        this.inputTarget.value = String(next);
        this._notify();
    }

    _notify() {
        this.inputTarget.dispatchEvent(new Event('input',  { bubbles: true }));
        this.inputTarget.dispatchEvent(new Event('change', { bubbles: true }));
    }
}
