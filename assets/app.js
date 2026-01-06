import { Tooltip } from 'bootstrap';

import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/theme.boostrap.css'
import 'bootstrap';
import './styles/app.css';
import './styles/ux-autocomplete-floating.css';
import 'leaflet/dist/leaflet.css';
import 'lightgallery/css/lightgallery.css'
import 'flatpickr/dist/flatpickr.min.css';
document.addEventListener('DOMContentLoaded', () => {
    new Tooltip(document.body, {
        selector: '[data-bs-toggle="tooltip"]',
        boundary: document.body,
        container: 'body',
        trigger: 'hover focus',
        delay: { show: 150, hide: 100 }
    });
});