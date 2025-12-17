import { Application } from '@hotwired/stimulus';

// Start a clean Stimulus instance for admin pages
// Do NOT use startStimulusApp() as it auto-loads ALL controllers from controllers.json
// including the autocomplete controller that conflicts with EasyAdmin
export const app = Application.start();

// Manually register only the controllers needed for admin
// This gives us full control and prevents conflicts with EasyAdmin's JavaScript
import PickLocationController from './controllers/pick_location_controller.js';
import MapController from '@symfony/ux-leaflet-map';

app.register('pick-location', PickLocationController);
app.register('symfony--ux-leaflet-map--map', MapController);

