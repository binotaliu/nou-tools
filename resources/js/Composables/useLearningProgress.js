import { nextTick, ref } from 'vue'

// `progressFormRef` must point at the scrollable form element.
export default function useLearningProgress(progressFormRef) {
  const showHorizontalGradient = ref(false)
  const showVerticalGradient = ref(false)

  function checkGradientVisibility() {
    const progressForm = progressFormRef.value

    if (!progressForm) {
      return
    }

    showHorizontalGradient.value =
      progressForm.scrollHeight > progressForm.clientHeight &&
      progressForm.scrollTop + progressForm.clientHeight <
        progressForm.scrollHeight
    showVerticalGradient.value =
      progressForm.scrollWidth > progressForm.clientWidth &&
      progressForm.scrollLeft + progressForm.clientWidth <
        progressForm.scrollWidth
  }

  async function init() {
    checkGradientVisibility()
    // The ref may not be bound yet on the first pass, so re-check once Vue
    // has flushed the DOM.
    await nextTick()
    checkGradientVisibility()
  }

  return {
    showHorizontalGradient,
    showVerticalGradient,
    checkGradientVisibility,
    init,
  }
}
