// assets/controllers/money_input_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        locale: { type: String,  default: (typeof navigator !== "undefined" ? navigator.language : "en-US") },
        decimals: { type: Number, default: 2 },
        grouping: { type: Boolean, default: true },
        groupWhileTyping: { type: Boolean, default: true }
    };

    connect() {
        // force text to avoid browser numeric quirks
        if (!this.element.getAttribute("type")) this.element.setAttribute("type", "text");
        const v = this.element.value?.trim();
        if (v !== "") this.formatCurrent(/*fixed=*/true, this.effectiveGrouping());
    }

    onFocus() {
        // Re-apply pretty formatting on focus so separators are visible immediately
        this.formatCurrent(/*fixed=*/false, this.effectiveGrouping());

        // place caret at end
        const len = this.element.value?.length ?? 0;
        this.element.setSelectionRange(len, len);
    }

    onInput() {
        const el = this.element;
        const caretBefore = el.selectionStart ?? el.value.length;

        const ds = this.decimalSep();
        const notAllowed = new RegExp(`[^0-9${this.escape(ds)}-]`, "g");
        let raw = el.value.replace(/\s|\u00A0/g, "").replace(notAllowed, "");

        const first = raw.indexOf(ds);
        if (first !== -1) raw = raw.slice(0, first + 1) + raw.slice(first + 1).replaceAll(ds, "");

        let [intPart, decPart = ""] = raw.split(ds);

        const leftOfCaretDigits = this.countDigits(el.value.slice(0, caretBefore));

        const sign = intPart.startsWith("-") ? "-" : "";
        intPart = intPart.replace(/[^0-9]/g, "");

        // Group while typing if enabled
        if (this.groupWhileTypingValue) {
            intPart = this.formatInteger(sign + intPart);
        } else {
            intPart = sign + intPart;
        }

        let next = intPart;
        if (decPart !== "") {
            decPart = decPart.replace(/[^0-9]/g, "").slice(0, this.decimalsValue);
            next += ds + decPart;
        }

        el.value = next;

        const newCaret = this.caretFromDigits(el.value, leftOfCaretDigits);
        el.setSelectionRange(newCaret, newCaret);
    }

    onBlur() {
        // Keep grouping on blur if either grouping or groupWhileTyping is true
        this.formatCurrent(/*fixed=*/true, this.effectiveGrouping());
    }

    // ---- helpers ----
    effectiveGrouping() {
        // Persist separators if either option is on
        return !!(this.groupingValue || this.groupWhileTypingValue);
    }

    formatCurrent(fixed, groupingOverride) {
        const n = this.parse(this.element.value);
        if (n === null || isNaN(n)) return;
        this.element.value = this.toLocaleString(n, {
            grouping: groupingOverride !== undefined ? groupingOverride : this.groupingValue,
            fixed: !!fixed
        });
    }

    parse(str) {
        if (typeof str !== "string") return null;
        let s = str.trim();
        if (!s) return null;

        const ds = this.decimalSep();
        const gs = this.groupSep();

        s = s.replace(new RegExp(`[\\s${this.escape(gs)}\u00A0]`, "g"), "");
        if (ds !== ".") s = s.replace(ds, ".");
        s = s.replace(/[^0-9.\-]/g, "");

        const num = Number(s);
        return isNaN(num) ? null : num;
    }

    toLocaleString(num, { grouping, fixed }) {
        const opts = {
            useGrouping: !!grouping,
            minimumFractionDigits: fixed ? this.decimalsValue : 0,
            maximumFractionDigits: this.decimalsValue
        };
        return new Intl.NumberFormat(this.localeValue || "en-US", opts).format(num);
    }

    formatInteger(intStrWithOptionalSign) {
        const sign = intStrWithOptionalSign.startsWith("-") ? "-" : "";
        const digits = intStrWithOptionalSign.replace(/[^0-9]/g, "");
        if (digits === "") return sign;
        const n = Number(digits);
        const formatted = new Intl.NumberFormat(this.localeValue || "en-US", {
            useGrouping: true,
            maximumFractionDigits: 0
        }).format(n);
        return sign + formatted;
    }

    decimalSep() {
        const parts = Intl.NumberFormat(this.localeValue || "en-US").formatToParts(1.1);
        return (parts.find(p => p.type === "decimal")?.value) ?? ".";
    }

    groupSep() {
        const parts = Intl.NumberFormat(this.localeValue || "en-US").formatToParts(1000);
        return (parts.find(p => p.type === "group")?.value) ?? ",";
    }

    countDigits(s) {
        return (s.match(/\d/g) || []).length;
    }

    caretFromDigits(formatted, targetDigitsLeft) {
        let count = 0;
        for (let i = 0; i < formatted.length; i++) {
            if (/\d/.test(formatted[i])) count++;
            if (count >= targetDigitsLeft) return i + 1;
        }
        return formatted.length;
    }

    escape(ch) {
        return ch.replace(/[-/\\^$*+?.()|[\]{}]/g, "\\$&");
    }
}
