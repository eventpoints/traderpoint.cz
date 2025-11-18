import { Controller } from '@hotwired/stimulus';
import { loadStripe } from '@stripe/stripe-js';

export default class extends Controller {
    static values = {
        publicKey: String,
        privateKey: String,
    };

    static targets = [
        'cardElement',
        'cardholderName',
        'cardErrors',
        'paymentMethod',
        'submitButton',
        'processing',
    ];

    async connect() {
        if (!this.publicKeyValue || !this.privateKeyValue) {
            console.error('Stripe public key or client secret missing on paywall.');
            return;
        }

        this.stripe = await loadStripe(this.publicKeyValue);
        if (!this.stripe) {
            console.error('Failed to initialize Stripe.js');
            return;
        }

        this.elements = this.stripe.elements();
        this.card = this.elements.create('card', { hidePostalCode: true });
        this.card.mount(this.cardElementTarget);

        this.card.on('change', (event) => {
            if (event.error) {
                this.cardErrorsTarget.textContent = event.error.message;
            } else {
                this.cardErrorsTarget.textContent = '';
            }
        });
    }

    async submit(event) {
        event.preventDefault();

        if (!this.card) {
            return;
        }

        const name = this.cardholderNameTarget.value.trim();
        if (!name) {
            this.cardErrorsTarget.textContent = this.data.get('nameRequiredMessage') ||
                'Please enter the name on the card.';
            return;
        }

        this.submitButtonTarget.disabled = true;
        this.processingTarget.classList.remove('d-none');
        this.cardErrorsTarget.textContent = '';

        const { setupIntent, error } = await this.stripe.confirmCardSetup(
            this.privateKeyValue,
            {
                payment_method: {
                    card: this.card,
                    billing_details: {
                        name,
                        email: this.element.dataset.email || '',
                    },
                },
            }
        );

        if (error) {
            this.cardErrorsTarget.textContent =
                error.message ||
                this.data.get('genericErrorMessage') ||
                'There was a problem confirming your card. Please try again.';

            this.submitButtonTarget.disabled = false;
            this.processingTarget.classList.add('d-none');
            return;
        }

        // Success: send payment_method ID to backend
        this.paymentMethodTarget.value = setupIntent.payment_method;

        // Submit the underlying form
        this.element.submit();
    }
}
