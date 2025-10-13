import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input'];
    static values = {
        hoursPerDay:   { type: Number, default: 8 },
        hoursPerWeek:  { type: Number, default: 40 },
        weeksPerMonth: { type: Number, default: 4 },
    }

    // absolute hours
    set(event) {
        event.preventDefault();
        if (!this.hasInputTarget) return;
        const amount = Number(event.params.amount ?? 0);
        this.inputTarget.value = String(amount);
        this._notify();
    }

    // add hours
    add(event) {
        event.preventDefault();
        if (!this.hasInputTarget) return;
        const amount = Number(event.params.amount ?? 0);
        const current = Number(this.inputTarget.value || 0);
        this.inputTarget.value = String(current + amount);
        this._notify();
    }

    // semantic “plus” helpers
    plusDay(event)   { event.preventDefault(); this._addAbs(this.hoursPerDayValue); }
    plusWeek(event)  { event.preventDefault(); this._addAbs(this.hoursPerWeekValue); }
    plusMonth(event) { event.preventDefault(); this._addAbs(this.hoursPerWeekValue * this.weeksPerMonthValue); }

    // reset to explicit value (e.g., engagement total budget in hours if you want)
    reset(event) {
        event.preventDefault();
        if (!this.hasInputTarget) return;
        let v = event.params.price ?? event.params.amount ?? 0; // tolerate either param name
        if (typeof v === 'string') v = v.replace(/[^\d.-]/g, '');
        this.inputTarget.value = String(Number(v) || 0);
        this._notify();
    }

    clear(event) {
        event.preventDefault();
        if (!this.hasInputTarget) return;
        this.inputTarget.value = '';
        this._notify();
    }

    // ---- internal
    _addAbs(hours) {
        if (!this.hasInputTarget) return;
        const current = Number(this.inputTarget.value || 0);
        this.inputTarget.value = String(current + hours);
        this._notify();
    }

    _notify() {
        this.inputTarget.dispatchEvent(new Event('input',  { bubbles: true }));
        this.inputTarget.dispatchEvent(new Event('change', { bubbles: true }));
    }
}
