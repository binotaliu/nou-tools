import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/css/schedule-print.css',
        'resources/js/app.js',
        'resources/js/leaflet.js',
        'resources/js/echo.js',
        'resources/css/filament/admin/theme.css',
      ],
      refresh: true,
    }),
    tailwindcss(),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
    // Laravel serves the HTML, so there is no index.html for the plugin to
    // inject into; import the devtools client from the app entry instead.
    vueDevTools({ appendTo: 'resources/js/app.js' }),
  ],
  server: {
    watch: {
      ignored: ['**/storage/framework/views/**'],
    },
  },
})
