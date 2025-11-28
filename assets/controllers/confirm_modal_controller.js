// assets/controllers/confirm_modal_controller.js
import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

// Shared references to the modal DOM + instance
let modalElement = null;
let modalInstance = null;
let titleElement = null;
let bodyElement = null;
let confirmButton = null;
let cancelButton = null;

function initModal() {
    if (modalElement) {
        return;
    }

    modalElement = document.getElementById('confirmDeleteJobModal');
    if (!modalElement) {
        console.warn('confirm-modal: #confirmDeleteJobModal not found in DOM');
        return;
    }

    modalInstance = Modal.getOrCreateInstance(modalElement);

    titleElement = modalElement.querySelector('[data-role="title"]');
    bodyElement = modalElement.querySelector('[data-role="body"]');
    confirmButton = modalElement.querySelector('[data-role="confirm"]');
    cancelButton = modalElement.querySelector('[data-role="cancel"]');

    if (confirmButton) {
        confirmButton.addEventListener('click', () => {
            const formId = modalElement.dataset.formId;
            if (formId) {
                const form = document.getElementById(formId);
                if (form) {
                    form.submit();
                }
            }
            modalInstance.hide();
        });
    }
}

export default class extends Controller {
    static values = {
        formId: String,
        title: String,
        body: String,
        confirmLabel: String,
        cancelLabel: String,
    };

    connect() {
        initModal();
        this.element.addEventListener('click', this.open);
    }

    disconnect() {
        this.element.removeEventListener('click', this.open);
    }

    open = (event) => {
        event.preventDefault();

        if (!modalElement || !modalInstance) {
            return;
        }

        if (titleElement) {
            titleElement.textContent = this.titleValue || 'Confirm';
        }

        if (bodyElement) {
            bodyElement.textContent = this.bodyValue || '';
        }

        if (confirmButton && this.hasConfirmLabelValue) {
            confirmButton.textContent = this.confirmLabelValue;
        }

        if (cancelButton && this.hasCancelLabelValue) {
            cancelButton.textContent = this.cancelLabelValue;
        }

        // Store form id so confirm button knows what to submit
        modalElement.dataset.formId = this.formIdValue;

        modalInstance.show();
    };
}
