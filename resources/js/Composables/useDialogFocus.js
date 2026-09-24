import { nextTick, onUnmounted, watch } from 'vue'

const FOCUSABLE = [
  'a[href]',
  'button:not([disabled])',
  'input:not([disabled]):not([type="hidden"])',
  'select:not([disabled])',
  'textarea:not([disabled])',
  '[tabindex]:not([tabindex="-1"])',
].join(',')

function visibleFocusables(container) {
  return [...container.querySelectorAll(FOCUSABLE)].filter(
    element => element.getClientRects().length > 0
  )
}

// Modal focus for the study room's dialogs: on open, focus moves into the
// dialog (`initialFocus()` if given, else its first focusable control);
// Tab and Shift+Tab wrap inside it; on close, focus returns to whatever
// opened it, if that is still on the page.
//
// `isOpen` is a getter; `container` a ref to the dialog element. Bind the
// returned `onKeydown` on the dialog.
export default function useDialogFocus(container, isOpen, options = {}) {
  let opener = null

  async function focusInside() {
    await nextTick()

    const root = container.value

    if (!root) {
      return
    }

    const preferred = options.initialFocus ? options.initialFocus() : null
    const target = preferred || visibleFocusables(root)[0] || root

    target.focus()
  }

  function restoreFocus() {
    const target = opener
    opener = null

    // An accesskey proxy (off-screen, aria-hidden) is no place to land.
    if (
      target &&
      target.isConnected &&
      target.getClientRects().length > 0 &&
      !target.closest('[aria-hidden="true"]')
    ) {
      target.focus()
    } else if (options.fallbackFocus) {
      options.fallbackFocus()?.focus()
    }
  }

  watch(
    isOpen,
    open => {
      if (open) {
        opener = document.activeElement
        focusInside()
      } else if (opener) {
        nextTick(restoreFocus)
      }
    },
    { immediate: true }
  )

  onUnmounted(() => {
    if (opener) {
      restoreFocus()
    }
  })

  function onKeydown(event) {
    if (event.key !== 'Tab' || !container.value) {
      return
    }

    const focusables = visibleFocusables(container.value)

    if (focusables.length === 0) {
      event.preventDefault()
      return
    }

    const first = focusables[0]
    const last = focusables[focusables.length - 1]
    const active = document.activeElement

    if (event.shiftKey && (active === first || active === container.value)) {
      event.preventDefault()
      last.focus()
    } else if (!event.shiftKey && active === last) {
      event.preventDefault()
      first.focus()
    } else if (!container.value.contains(active)) {
      event.preventDefault()
      first.focus()
    }
  }

  return { onKeydown }
}
