import './bootstrap'

const trackAnalyticsEvent = (eventName, params = {}) => {
  if (typeof window.gtag !== 'function' || !eventName) {
    return
  }

  window.gtag('event', eventName, params)
}

document.addEventListener('click', event => {
  const target = event.target.closest('[data-analytics-event]')

  if (!target) {
    return
  }

  const { analyticsEvent, analyticsFeature, analyticsLabel } = target.dataset

  trackAnalyticsEvent(analyticsEvent, {
    feature: analyticsFeature,
    label: analyticsLabel,
  })
})
