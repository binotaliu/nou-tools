export default function linksCenterMap(config) {
  return {
    centers: config.centers,
    regions: config.regions,
    mapTileLayer: config.mapTileLayer,
    mapTileLayerAttribution: config.mapTileLayerAttribution,
    selectedKey: null,
    map: null,
    marker: null,
    mapInitialized: false,
    showMapSelectionModal: false,

    get selectedCenter() {
      return (
        this.centers.find(center => center.key === this.selectedKey) ?? null
      )
    },

    init() {
      if (window.leaflet) {
        this.initMap()
      }
    },

    initMap() {
      if (
        this.mapInitialized ||
        !this.$refs.mapContainer ||
        !window.leaflet ||
        !this.selectedCenter ||
        this.$store.network.offline
      ) {
        return
      }

      this.mapInitialized = true

      this.map = window.leaflet
        .map(this.$refs.mapContainer, {
          zoomControl: true,
          boxZoom: true,
          doubleClickZoom: false,
          dragging: true,
          keyboard: false,
          scrollWheelZoom: true,
          touchZoom: true,
        })
        .setView(
          [this.selectedCenter.latitude, this.selectedCenter.longitude],
          16
        )

      this.marker = window.leaflet
        .marker([this.selectedCenter.latitude, this.selectedCenter.longitude])
        .addTo(this.map)

      this.marker.bindPopup(this.selectedCenter.name)

      window.leaflet
        .tileLayer(this.mapTileLayer, {
          attribution: this.mapTileLayerAttribution,
        })
        .addTo(this.map)
    },

    selectCenter(key) {
      this.selectedKey = key

      if (!this.selectedCenter) {
        return
      }

      const latlng = [
        this.selectedCenter.latitude,
        this.selectedCenter.longitude,
      ]

      if (!this.mapInitialized) {
        this.$nextTick(() => this.initMap())
        return
      }

      this.map.setView(latlng, 16)
      this.marker.setLatLng(latlng)
      this.marker.bindPopup(this.selectedCenter.name)
    },

    openMapSelectionModal() {
      if (
        !this.selectedCenter ||
        !this.selectedCenter.latitude ||
        !this.selectedCenter.longitude
      ) {
        return
      }
      this.showMapSelectionModal = true
    },

    closeMapSelectionModal() {
      this.showMapSelectionModal = false
    },

    openInMap(mapService, overrideUrl = null) {
      const lat = this.selectedCenter.latitude
      const lon = this.selectedCenter.longitude
      const label = encodeURIComponent(this.selectedCenter.name)

      let url = ''

      switch (mapService) {
        case 'osm':
          url = `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lon}&zoom=16&layers=M`
          break
        case 'apple':
          url = `maps://maps.apple.com/?q=${label}&ll=${lat},${lon}&z=16`
          break
        case 'google':
          url =
            overrideUrl ??
            `https://maps.google.com/maps?q=${label}@${lat},${lon}&z=16`
          break
      }

      if (url) {
        window.open(url, '_blank')
        this.closeMapSelectionModal()
      }
    },
  }
}
