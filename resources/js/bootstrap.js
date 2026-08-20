import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import '../../vendor/jeffgreco13/filament-breezy/resources/js/index.js';
