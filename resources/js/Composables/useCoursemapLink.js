const COURSEMAP_ORIGIN = 'https://coursemap.nou.edu.tw'
const COURSEMAP_HOME = `${COURSEMAP_ORIGIN}/`

// coursemap's detail pages answer with an error unless the visitor already
// holds the ASP session cookie its homepage hands out. That cookie is
// SameSite-less (so Lax), which rules out warming it from a hidden iframe:
// only a top-level visit sets it. So the click opens the homepage in a new tab
// and, after a short delay, points that same tab at the real page. A
// cross-origin `load` event is unreadable, hence the fixed delay.
export const COURSEMAP_WARMUP_MS = 1200

export default function useCoursemapLink(delay = COURSEMAP_WARMUP_MS) {
  // Bound to `@click` on the <a>; the href stays the real page, so
  // middle-click, copy-link and a blocked popup still get the plain link.
  function open(event, url) {
    if (
      event.defaultPrevented ||
      event.button !== 0 ||
      event.metaKey ||
      event.ctrlKey ||
      event.shiftKey ||
      event.altKey ||
      !url?.startsWith(`${COURSEMAP_ORIGIN}/`)
    ) {
      return
    }

    const tab = window.open(COURSEMAP_HOME, '_blank')

    if (!tab) {
      return
    }

    event.preventDefault()

    setTimeout(() => {
      if (tab.closed) {
        return
      }

      tab.location.href = url

      try {
        tab.opener = null
      } catch (e) {
        // Detaching is best effort.
      }
    }, delay)
  }

  return { open }
}
