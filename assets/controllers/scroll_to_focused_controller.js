import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        param: { type: String, default: 'focused' },
        behavior: { type: String, default: 'smooth' },
    };

    connect() {
        const url = new URL(window.location.href);
        const focusedValue = url.searchParams.get(this.paramValue);

        if (!focusedValue) {
            return;
        }

        let target =
            document.getElementById(`message-${focusedValue}`) ||
            document.querySelector(`[data-message-id="${focusedValue}"]`);

        if (!target) {
            return;
        }

        target.scrollIntoView({
            behavior: this.behaviorValue,
            block: 'center',
        });

        target.classList.add('is-focused');
    }
}
