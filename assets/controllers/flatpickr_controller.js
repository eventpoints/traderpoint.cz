import { Controller } from '@hotwired/stimulus';
import flatpickr from 'flatpickr';

export default class extends Controller {
    static values = {
        enableTime: { type: Boolean, default: false },
        // optional override – if not provided, we choose based on enableTime
        dateFormat: String,
    };

    connect() {
        const dateFormat = this.hasDateFormatValue
            ? this.dateFormatValue
            : (this.enableTimeValue ? 'Y-m-d H:i' : 'Y-m-d');

        this.picker = flatpickr(this.element, {
            enableTime: this.enableTimeValue,
            dateFormat,
        });
    }

    disconnect() {
        if (this.picker) {
            this.picker.destroy();
            this.picker = null;
        }
    }
}
