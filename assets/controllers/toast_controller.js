// assets/controllers/toast_controller.js
import { Controller } from '@hotwired/stimulus';
import { Toast } from 'bootstrap';

export default class extends Controller {
    static targets = ['container'];

    connect() {
        this.showToasts();
    }

    showToasts() {
        this.containerTarget.querySelectorAll('.toast').forEach(toastElement => {
            const toast = new Toast(toastElement);
            toast.show();
        });
    }
}
