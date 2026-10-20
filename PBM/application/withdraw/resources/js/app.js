import './bootstrap';
import './toast';
import 'flatpickr/dist/flatpickr.min.css';

import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';
import { Thai } from 'flatpickr/dist/l10n/th.js';

window.Alpine = Alpine;
window.flatpickr = flatpickr;
window.flatpickrThai = Thai;

Alpine.start();
