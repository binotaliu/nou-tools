import { nextTick, onBeforeUnmount, onMounted, watch } from 'vue'

// Article Markdown is rendered to HTML server-side (see
// src/Domains/Articles/Markdown/) and mounted with v-html, so Vue never
// compiles it and the interactive containers can't be Vue components. This
// attaches their behaviour to the raw DOM instead.
//
// `rootRef` is the element holding the rendered Markdown. `sources` are
// getters for the reactive values it was rendered from; watching them is not
// optional, because Inertia reuses the page component when navigating
// article -> article, so v-html swaps the content without a remount and the
// replacement markup would otherwise never be hydrated.
export default function useMarkdownContainers(rootRef, sources = []) {
  let cleanups = []

  function teardown() {
    cleanups.forEach(cleanup => cleanup())
    cleanups = []
  }

  async function hydrate() {
    teardown()
    await nextTick()

    const root = rootRef.value

    if (!root) {
      return
    }

    cleanups = [
      ...enhanceTabs(root),
      ...enhanceChecklists(root),
      ...enhanceCountdowns(root),
    ]
  }

  onMounted(hydrate)
  onBeforeUnmount(teardown)

  if (sources.length > 0) {
    watch(sources, hydrate)
  }

  return { hydrate }
}

// `:::tabs` — see TabsRenderer. Panels arrive visible and unmarked so the
// content is readable without JavaScript; selecting the first one and
// revealing the tab strip (via data-enhanced) only happens here.
function enhanceTabs(root) {
  return Array.from(root.querySelectorAll('.md-tabs')).map(tabs => {
    // Scoped by direct descent: a nested :::tabs block has its own triggers
    // and panels, and a plain descendant query would let the outer group
    // drive them both.
    const triggers = Array.from(
      tabs.querySelectorAll(':scope > .md-tabs-list > .md-tabs-trigger')
    )
    const panels = Array.from(tabs.querySelectorAll(':scope > .md-tabs-panel'))

    function select(index) {
      triggers.forEach((trigger, position) => {
        const active = position === index
        trigger.setAttribute('aria-selected', active ? 'true' : 'false')
        trigger.dataset.active = active ? 'true' : 'false'
      })

      panels.forEach((panel, position) => {
        panel.hidden = position !== index
      })
    }

    const removeListeners = triggers.map((trigger, index) => {
      const onClick = () => select(index)

      trigger.addEventListener('click', onClick)

      return () => trigger.removeEventListener('click', onClick)
    })

    select(0)
    tabs.dataset.enhanced = 'true'

    return () => {
      removeListeners.forEach(remove => remove())
      delete tabs.dataset.enhanced
      panels.forEach(panel => {
        panel.hidden = false
      })
    }
  })
}

// `:::checklist` — see ChecklistRenderer, which already strips `disabled`
// and wraps each row in a <label>. Only persistence is left to do here, so
// the list still works without JavaScript; it just won't be remembered.
function enhanceChecklists(root) {
  return Array.from(root.querySelectorAll('.md-checklist')).map(checklist => {
    const storageKey = checklistStorageKey(checklist)
    const saved = readChecklistStates(storageKey)

    const removeListeners = Array.from(checklist.querySelectorAll('li')).map(
      (item, index) => {
        const checkbox = item.querySelector('input[type="checkbox"]')

        if (!checkbox) {
          return () => {}
        }

        if (typeof saved[index] === 'boolean') {
          checkbox.checked = saved[index]
        }

        item.dataset.checked = checkbox.checked ? 'true' : 'false'

        const onChange = () => {
          item.dataset.checked = checkbox.checked ? 'true' : 'false'
          writeChecklistStates(storageKey, checklist)
        }

        checkbox.addEventListener('change', onChange)

        return () => checkbox.removeEventListener('change', onChange)
      }
    )

    return () => removeListeners.forEach(remove => remove())
  })
}

// Keyed by page path plus the checklist's index among every .md-checklist on
// the page — counted across the whole document, not just this root, since an
// article mounts its sidebar and its body as two separate roots. Readers
// have ticks saved under this exact shape already, so it must not drift.
function checklistStorageKey(checklist) {
  const index = Array.from(document.querySelectorAll('.md-checklist')).indexOf(
    checklist
  )

  return `nou:article-checklist:${window.location.pathname}:${index >= 0 ? index : 0}:v1`
}

function readChecklistStates(storageKey) {
  try {
    const raw = localStorage.getItem(storageKey)

    if (!raw) {
      return []
    }

    const parsed = JSON.parse(raw)

    return Array.isArray(parsed) ? parsed.map(value => !!value) : []
  } catch (error) {
    return []
  }
}

function writeChecklistStates(storageKey, checklist) {
  const states = Array.from(
    checklist.querySelectorAll('input[type="checkbox"]')
  ).map(checkbox => checkbox.checked)

  try {
    localStorage.setItem(storageKey, JSON.stringify(states))
  } catch (error) {
    // Private browsing, or the quota is full. The checklist still works,
    // it just won't be remembered.
  }
}

// `:::countdown` — see CountdownRenderer, which already rendered the correct
// day count against Asia/Taipei "today". This only stops it going stale.
function enhanceCountdowns(root) {
  const items = Array.from(
    root.querySelectorAll('.md-countdown-item[data-countdown-start]')
  )

  if (items.length === 0) {
    return []
  }

  function refresh() {
    const today = window.NouTime.taipeiYmd(new Date())

    items.forEach(item => {
      const target = item.querySelector('[data-countdown-days]')

      if (!target) {
        return
      }

      target.textContent = countdownText(
        today,
        item.dataset.countdownStart,
        item.dataset.countdownEnd
      )
    })
  }

  refresh()

  // Daily-granularity data, so an hourly tick plus a refresh when the tab
  // comes back is enough for a long-lived or offline-restored tab. One timer
  // and one listener for the whole root, not one pair per item, so nothing
  // accumulates across repeated SPA navigations.
  const interval = setInterval(refresh, 60 * 60 * 1000)
  const onVisibilityChange = () => {
    if (!document.hidden) {
      refresh()
    }
  }

  document.addEventListener('visibilitychange', onVisibilityChange)

  return [
    () => {
      clearInterval(interval)
      document.removeEventListener('visibilitychange', onVisibilityChange)
    },
  ]
}

// Mirrors CountdownRenderer::daysText() so the client agrees with what the
// server already rendered. Anchored to Taipei rather than the viewer's zone
// because these are academic dates published on Taipei's calendar — same
// reasoning as useSchoolCalendar.js.
function countdownText(today, start, end) {
  if (today < start) {
    return `倒數 ${window.NouTime.diffInDaysYmd(today, start)} 天`
  }

  return today <= end ? '進行中' : '已結束'
}
