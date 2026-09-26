import { ref } from 'vue'

// `initial` mirrors { shareTitle, shareUrl, shareText? }. `shareText` is an
// optional blurb sent along with the link (and copied in front of it).
// `shareInputRef` is an
// optional ref to the <input> used for the copy-to-clipboard `execCommand`
// fallback.
export default function useArticleShare(initial, shareInputRef = null) {
  const showShareModal = ref(false)
  const copied = ref(false)
  const shareTitle = ref(initial.shareTitle)
  const shareUrl = ref(initial.shareUrl)
  const shareText = ref(initial.shareText ?? '')

  async function share() {
    if (navigator.share) {
      try {
        await navigator.share({
          title: shareTitle.value,
          ...(shareText.value ? { text: shareText.value } : {}),
          url: shareUrl.value,
        })
      } catch (e) {
        // User cancelled the share sheet; nothing to do.
      }

      return
    }

    showShareModal.value = true
  }

  async function copy() {
    try {
      await navigator.clipboard.writeText(
        shareText.value
          ? `${shareText.value}\n${shareUrl.value}`
          : shareUrl.value
      )
    } catch (e) {
      shareInputRef?.value?.select()
      document.execCommand('copy')
    }

    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  }

  return {
    showShareModal,
    copied,
    shareTitle,
    shareUrl,
    shareText,
    share,
    copy,
  }
}
