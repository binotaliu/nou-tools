export default function discountStoreReportForm(config) {
  return {
    storeName: config.storeName,
    hasCoordinates: config.hasCoordinates,
    latitude: config.latitude,
    longitude: config.longitude,
    address: config.address,
    shouldShowMap: config.shouldShowMap,
    mapTileLayer: null,
    mapTileLayerAttribution: null,
    hasPendingComment: config.hasPendingComment,
    showReportModal: false,
    showCommentModal: false,
    showMapSelectionModal: false,
    isValid: true,
    map: null,
    marker: null,
    mapInitialized: false,
    reportTurnstileWidgetId: null,
    commentTurnstileWidgetId: null,
    reportFormTurnstileChallengeExecuted: false,
    commentFormTurnstileChallengeExecuted: false,

    init() {
      // Read via data-* attributes rather than the x-data expression: Alpine's
      // CSP build parses string literals with a hand-rolled tokenizer that
      // doesn't decode \uXXXX escapes, which is what Blade's @js() directive
      // uses to keep HTML-bearing strings (e.g. this attribution's <a> tag)
      // safe inside an HTML attribute. The browser's own HTML parser decodes
      // entities in data-* attributes correctly, so we read the raw string
      // there instead.
      this.mapTileLayer = this.$el.dataset.mapTileLayer
      this.mapTileLayerAttribution = this.$el.dataset.mapTileLayerAttribution

      if (this.hasPendingComment) {
        this.openCommentModal()
      }

      if (this.shouldShowMap) {
        this.$nextTick(() => {
          this.initMap()
        })
      }
    },

    async initMap() {
      if (
        this.mapInitialized ||
        !this.shouldShowMap ||
        !this.$refs.mapContainer ||
        !window.leaflet
      ) {
        console.warn('地圖初始化失敗：', {
          mapInitialized: this.mapInitialized,
          shouldShowMap: this.shouldShowMap,
          mapContainerExists: !!this.$refs.mapContainer,
          leafletLoaded: !!window.leaflet,
        })
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
        .setView([this.latitude, this.longitude], 16)

      this.marker = window.leaflet
        .marker([this.latitude, this.longitude])
        .addTo(this.map)

      this.marker.bindPopup(this.storeName)

      window.leaflet
        .tileLayer(this.mapTileLayer, {
          attribution: this.mapTileLayerAttribution,
        })
        .addTo(this.map)
    },

    openMapSelectionModal() {
      if (!this.hasCoordinates) {
        return
      }
      this.showMapSelectionModal = true
    },

    closeMapSelectionModal() {
      this.showMapSelectionModal = false
    },

    openInMap(mapService) {
      const lat = this.latitude
      const lon = this.longitude
      const label = encodeURIComponent(this.storeName)

      let url = ''

      switch (mapService) {
        case 'osm':
          url = `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lon}&zoom=16&layers=M`
          break
        case 'apple':
          url = `maps://maps.apple.com/?q=${label}&ll=${lat},${lon}&z=16`
          break
        case 'google':
          url = `https://maps.google.com/maps?q=${label}@${lat},${lon}&z=16`
          break
      }

      if (url) {
        window.open(url, '_blank')
        this.closeMapSelectionModal()
      }
    },

    openReportModal(isValid) {
      this.isValid = isValid
      this.showReportModal = true
      this.$nextTick(() => {
        this.renderReportTurnstile()
      })
    },

    closeReportModal() {
      this.showReportModal = false
      this.reportFormTurnstileChallengeExecuted = false

      if (window.turnstile && this.reportTurnstileWidgetId !== null) {
        window.turnstile.remove(this.reportTurnstileWidgetId)
        this.reportTurnstileWidgetId = null
      }
    },

    openCommentModal() {
      this.showCommentModal = true
      this.$nextTick(() => {
        this.renderCommentTurnstile()
      })
    },

    closeCommentModal() {
      this.showCommentModal = false
      this.commentFormTurnstileChallengeExecuted = false

      if (window.turnstile && this.commentTurnstileWidgetId !== null) {
        window.turnstile.remove(this.commentTurnstileWidgetId)
        this.commentTurnstileWidgetId = null
      }
    },

    renderReportTurnstile() {
      this.renderTurnstile(
        'turnstile__store-report',
        widgetId => {
          this.reportTurnstileWidgetId = widgetId
        },
        () => {
          this.reportFormTurnstileChallengeExecuted = true
        },
        () => {
          this.reportFormTurnstileChallengeExecuted = false
        },
        () => this.showReportModal
      )
    },

    renderCommentTurnstile() {
      this.renderTurnstile(
        'turnstile__store-comment',
        widgetId => {
          this.commentTurnstileWidgetId = widgetId
        },
        () => {
          this.commentFormTurnstileChallengeExecuted = true
        },
        () => {
          this.commentFormTurnstileChallengeExecuted = false
        },
        () => this.showCommentModal
      )
    },

    renderTurnstile(
      containerId,
      onRendered,
      onSuccess,
      onInvalid,
      shouldRender
    ) {
      const container = document.getElementById(containerId)
      if (!container) {
        return
      }

      const tryRender = () => {
        if (!shouldRender()) {
          return
        }

        if (!window.turnstile) {
          setTimeout(tryRender, 100)
          return
        }

        const widgetId = window.turnstile.render(`#${containerId}`, {
          sitekey: container.dataset.sitekey,
          theme: container.dataset.theme,
          language: container.dataset.language,
          size: container.dataset.size,
          callback: () => {
            onSuccess()
          },
          'error-callback': () => {
            onInvalid()
          },
          'expired-callback': () => {
            onInvalid()
          },
        })

        onRendered(widgetId)
      }

      tryRender()
    },
  }
}
