import './bootstrap';
import './lazyload';

import Alpine from 'alpinejs'
window.Alpine = Alpine
// Registers the shareDialog component on the `alpine:init` event, so it must be
// imported before Alpine.start() fires that event below.
import './share';
Alpine.start()
