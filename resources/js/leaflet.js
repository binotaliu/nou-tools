import * as leaflet from 'leaflet'
import 'leaflet/dist/leaflet.css'

window.leaflet = leaflet

window.dispatchEvent(new CustomEvent('leafletLoaded'))
