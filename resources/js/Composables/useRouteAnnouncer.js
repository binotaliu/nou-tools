// Inertia visits swap the page without a load, which screen readers never
// notice. After a real page change this speaks the new document title through
// a polite live region and moves focus to <main>, as a full page load would.
//
// The region is a plain DOM node appended to <body> once, not part of
// AppLayout: pages wrap themselves in the layout, so it remounts on every
// visit and a region that appears together with its text is not announced.
//
// What counts as a real change:
// - The first navigation (the initial load) is ignored.
// - Same component and same path (form submit that stays, partial reload,
//   query-only filter change) is ignored entirely.
// - Same component, different path (term picker, schedule tabs) announces the
//   title but leaves focus alone, so the user keeps their place.
// - A different component announces and focuses <main>, unless focus is inside
//   a dialog or something deliberate outside <main> the new page already
//   claimed (study room moves focus itself when it takes a seat).
const REGION_ID = 'route-announcer'

let region = null
let installed = false
let previous = null
let timer = null

function ensureRegion() {
  if (region && document.body.contains(region)) {
    return region
  }

  region = document.createElement('div')
  region.id = REGION_ID
  region.setAttribute('role', 'status')
  region.setAttribute('aria-live', 'polite')
  region.setAttribute('aria-atomic', 'true')
  region.dataset.testid = 'route-announcer'
  region.className = 'sr-only'
  document.body.appendChild(region)

  return region
}

function announce(text) {
  const target = ensureRegion()

  target.textContent = ''
  setTimeout(() => {
    target.textContent = text
  }, 50)
}

function pathOf(url) {
  return String(url).split('?')[0].split('#')[0]
}

function shouldMoveFocus() {
  const active = document.activeElement

  if (!active || active === document.body) {
    return true
  }

  if (active.closest('[role="dialog"], dialog[open]')) {
    return false
  }

  // Focus already sits in the new page's content: something moved it on purpose.
  const main = document.getElementById('main-content')

  return !(main && main !== active && main.contains(active))
}

export function handleNavigation(page) {
  const current = { component: page.component, path: pathOf(page.url) }
  const before = previous

  previous = current

  if (!before) {
    return
  }

  const sameComponent = before.component === current.component

  if (sameComponent && before.path === current.path) {
    return
  }

  clearTimeout(timer)

  // <Head> updates the title after the navigate event, so wait a beat.
  timer = setTimeout(() => {
    announce(document.title)

    if (sameComponent || !shouldMoveFocus()) {
      return
    }

    document.getElementById('main-content')?.focus({ preventScroll: true })
  }, 100)
}

export default function useRouteAnnouncer(router) {
  if (installed) {
    return
  }

  installed = true
  ensureRegion()
  router.on('navigate', event => handleNavigation(event.detail.page))
}
