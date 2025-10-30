import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input'];

    // -- Actions --
    reset(event) {
        event.preventDefault();
        const el = this._el(); if (!el) return;

        let price = event.params.price;
        if (price == null) return;

        const n = this._parse(String(price), this._locale(el));
        const fixed = this._round(n, this._decimals(el));
        el.value = String(fixed);
        this._notify(el);
    }

    addPercentage(event) {
        event.preventDefault();
        const el = this._el(); if (!el) return;

        const pct = Number(event.params.pct || 0);
        const cur = this._parse(el.value, this._locale(el)) || 0;

        const decimals = this._decimals(el);
        const next = this._round(cur + (cur * pct / 100), decimals);

        el.value = String(next);
        this._notify(el);
    }

    minusPct(event) {
        event.preventDefault();
        const el = this._el(); if (!el) return;

        const pct = Number(event.params.pct || 0);
        const cur = this._parse(el.value, this._locale(el)) || 0;

        const decimals = this._decimals(el);
        const next = Math.max(0, this._round(cur - (cur * pct / 100), decimals));

        el.value = String(next);
        this._notify(el);
    }

    // -- Helpers --
    _el() {
        if (this.hasInputTarget) return this.inputTarget;
        return this.element.querySelector('input, [contenteditable="true"]');
    }

    _notify(el) {
        el.dispatchEvent(new Event('input',  { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    _locale(el) {
        return el.dataset.moneyInputLocaleValue || navigator.language || 'en-US';
    }

    _decimals(el) {
        const d = Number(el.dataset.moneyInputDecimalsValue);
        return Number.isFinite(d) ? d : 2; // default 2
    }

    _decimalSep(locale) {
        const parts = Intl.NumberFormat(locale).formatToParts(1.1);
        return parts.find(p => p.type === 'decimal')?.value || '.';
    }

    _groupSep(locale) {
        const parts = Intl.NumberFormat(locale).formatToParts(1000);
        return parts.find(p => p.type === 'group')?.value || ',';
    }

    _parse(str, locale) {
        if (!str) return NaN;
        const ds = this._decimalSep(locale);
        const gs = this._groupSep(locale);
        let s = String(str).trim();
        s = s.replace(new RegExp(`[\\s${this._esc(gs)}\\u00A0]`, 'g'), '');
        if (ds !== '.') s = s.replaceAll(ds, '.');
        s = s.replace(/[^0-9.\-]/g, '');
        const n = Number(s);
        return isNaN(n) ? NaN : n;
    }

    _round(n, decimals) {
        const m = Math.pow(10, decimals);
        return Math.round((n + Number.EPSILON) * m) / m;
    }

    _esc(ch) {
        return ch.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&');
    }
}
