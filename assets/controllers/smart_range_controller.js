// assets/controllers/smart_range_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        scale: Number,
        currency: String,
        prefix: String,
        suffix: String,
        decimals: Number,
        locale: String,
        outputId: String
    }

    connect() {
        this.outputEl = document.getElementById(this.outputIdValue);
        // Fail fast so you see it in DevTools if the id is wrong
        if (!this.outputEl) {
            console.warn('[smart-range] output element not found:', this.outputIdValue);
            return;
        }

        this._render(); // initial
        this.element.addEventListener('input', this._render);
        this.element.addEventListener('change', this._render);
    }

    disconnect() {
        this.element.removeEventListener('input', this._render);
        this.element.removeEventListener('change', this._render);
    }

    _render = () => {
        if (!this.outputEl) return;

        const raw = Number(this.element.value ?? 0);
        const scale = this.hasScaleValue ? (this.scaleValue || 1) : 1;
        const base = raw / scale;

        const decimals = this.hasDecimalsValue ? this.decimalsValue : 0;
        const locale = this.hasLocaleValue ? this.localeValue : undefined;

        let out;
        if (this.hasCurrencyValue && this.currencyValue) {
            out = new Intl.NumberFormat(locale, {
                style: 'currency',
                currency: this.currencyValue,
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(base);
        } else {
            out = new Intl.NumberFormat(locale, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(base);
        }

        if (this.hasPrefixValue && this.prefixValue) out = `${this.prefixValue}${out}`;
        if (this.hasSuffixValue && this.suffixValue) out = `${out}${this.suffixValue}`;

        this.outputEl.textContent = out;
    }
}
