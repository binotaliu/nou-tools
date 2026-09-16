import axios from 'axios'
window.axios = axios

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// Send the CSRF token on axios POSTs so they aren't rejected with a 419;
// Laravel emits the token in a <meta> tag (see resources/views/app.blade.php).
const csrfToken = document.querySelector('meta[name="csrf-token"]')

if (csrfToken) {
  window.axios.defaults.headers.common['X-CSRF-TOKEN'] =
    csrfToken.getAttribute('content')
}
