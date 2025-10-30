// assets/controllers/money_input_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        locale: { type: String, default: (typeof navigator !== "undefined" ? navigator.language : "en-US") },
        decimals: { type: Number, default: 2 },
        grouping: { type: Boolean, default: true },
        groupWhileTyping: { type: Boolean, default: true } // 👈 live commas
    };

    connect() {
        // force text to avoid browser numeric quirks
        if (!this.element.getAttribute("type")) this.element.setAttribute("type", "text");
        const v = this.element.value?.trim();
        if (v !== "") this.formatCurrent(/*fixed=*/true);
    }

    onFocus() {
        // Keep grouping while typing if enabled (no ungrouping step)
        // Ensure caret is not stuck
        const len = this.element.value?.length ?? 0;
        this.element.setSelectionRange(len, len);
    }

    onInput(e) {
        const el = this.element;
        const caretBefore = el.selectionStart ?? el.value.length;

        // Clean input: allow digits and one decimal separator
        const ds = this.decimalSep();
        const notAllowed = new RegExp(`[^0-9${this.escape(ds)}-]`, "g");
        let raw = el.value.replace(/\s|\u00A0/g, "").replace(notAllowed, "");

        // Collapse multiple decimals
        const first = raw.indexOf(ds);
        if (first !== -1) raw = raw.slice(0, first + 1) + raw.slice(first + 1).replaceAll(ds, "");

        // Split integer/decimal parts by locale decimal sep
        let [intPart, decPart = ""] = raw.split(ds);

        // Track how many digits are to the left of the caret BEFORE formatting
        const leftOfCaretDigits = this.countDigits(el.value.slice(0, caretBefore));

        // Strip non-digits from intPart except leading minus
        const sign = intPart.startsWith("-") ? "-" : "";
        intPart = intPart.replace(/[^0-9]/g, "");
        // Format integer part with grouping (commas in en-US)
        if (this.groupWhileTypingValue) {
            intPart = this.formatInteger(sign + intPart);
        } else {
            intPart = sign + intPart;
        }

        // Rebuild value
        let next = intPart;
        if (decPart !== "") {
            // Keep only digits in decimal part, limit to configured decimals
            decPart = decPart.replace(/[^0-9]/g, "").slice(0, this.decimalsValue);
            next += ds + decPart;
        }

        // Apply
        el.value = next;

        // Restore caret so it feels natural
        const newCaret = this.caretFromDigits(el.value, leftOfCaretDigits);
        el.setSelectionRange(newCaret, newCaret);
    }

    onBlur() {
        // Pretty-print with fixed decimals on blur
        this.formatCurrent(/*fixed=*/true);
    }

    // ---- helpers ----
    formatCurrent(fixed) {
        const n = this.parse(this.element.value);
        if (n === null || isNaN(n)) return;
        this.element.value = this.toLocaleString(n, {
            grouping: this.groupingValue,
            fixed: !!fixed
        });
    }

    parse(str) {
        if (typeof str !== "string") return null;
        let s = str.trim();
        if (!s) return null;

        const ds = this.decimalSep();
        const gs = this.groupSep();

        // remove spaces & group seps
        s = s.replace(new RegExp(`[\\s${this.escape(gs)}\u00A0]`, "g"), "");
        // normalize decimal to dot
        if (ds !== ".") s = s.replace(ds, ".");
        // keep digits, dot, minus
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
        // Use Intl on the integer portion to get locale grouping (commas in en-US)
        const sign = intStrWithOptionalSign.startsWith("-") ? "-" : "";
        const digits = intStrWithOptionalSign.replace(/[^0-9]/g, "");
        if (digits === "") return sign;
        const n = Number(digits);
        // Format without decimals
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
        // Place caret so that the same count of digits remains on the left
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