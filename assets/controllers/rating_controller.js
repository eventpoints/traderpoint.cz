import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'current'];

    connect() {
        this.updateCurrentValue(this.inputTarget.value);
    }

    updateValue() {
        this.updateCurrentValue(this.inputTarget.value);
    }

    updateCurrentValue(value) {
        this.currentTarget.textContent = value;
    }
}