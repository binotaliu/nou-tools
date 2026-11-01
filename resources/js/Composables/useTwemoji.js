// twemoji renders emoji consistently across platforms. It is dynamically
// imported rather than loaded globally, since only the pages that draw
// people and cups need it (the study room and the café) — mirrors the echo.js
// import. Vue keeps patching the text it rendered, so a MutationObserver
// re-parses whatever changed.
export default function useTwemoji() {
  let observer = null
  let rafHandle = null

  // VoiceOver reads an <img> as 「圖像」 whatever its alt says, so every emoji
  // image is hidden from screen readers and followed by an sr-only twin that
  // reads like the plain character did. The twin carries the character in
  // `data-emoji-text`, spoken through CSS generated content (app.css), so the
  // next parse has no text node to turn into yet another image.
  function speakEmojiAsText(root) {
    for (const image of root.querySelectorAll('img.emoji:not([aria-hidden])')) {
      const twin = document.createElement('span')
      twin.className = 'sr-only'
      twin.dataset.emojiText = image.alt
      image.setAttribute('aria-hidden', 'true')
      image.after(twin)
    }
  }

  // Twemoji's own fallback when an image fails to load puts the character
  // back as text, which would then be spoken twice alongside its twin.
  function restoreEmojiText() {
    if (this.nextSibling?.dataset?.emojiText !== undefined) {
      this.nextSibling.remove()
    }

    this.replaceWith(this.alt)
  }

  function parse(root) {
    if (typeof window.twemoji !== 'undefined') {
      window.twemoji.parse(root, { onerror: restoreEmojiText })
      speakEmojiAsText(root)
    }
  }

  function scheduleParse(root) {
    if (rafHandle) {
      return
    }

    rafHandle = requestAnimationFrame(() => {
      rafHandle = null
      parse(root)
    })
  }

  function observe(root) {
    parse(root)

    observer = new MutationObserver(() => scheduleParse(root))
    observer.observe(root, {
      childList: true,
      subtree: true,
      characterData: true,
    })
  }

  // Loads twemoji on first use, parses `root` and keeps it parsed.
  async function start(root) {
    if (!window.twemoji) {
      const { default: twemoji } = await import('@twemoji/api')
      window.twemoji = twemoji
    }

    observe(root)
  }

  function stop() {
    if (observer) {
      observer.disconnect()
      observer = null
    }
  }

  return { start, stop }
}
