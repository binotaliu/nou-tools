import { nextTick, ref } from 'vue'

// Vue port of the `nouLearningProgress` Alpine.data() component
// (resources/js/alpine-components.js). `progressFormRef` mirrors the
// Alpine version's `$refs.progressForm` and must point at the scrollable
// form element; `formId` mirrors the `#progress-form` id used for the
// fallback native submit.
export default function useLearningProgress(
  progressFormRef,
  formId = 'progress-form'
) {
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

  function submitProgressForm() {
    document.getElementById(formId)?.submit()
  }

  return {
    showHorizontalGradient,
    showVerticalGradient,
    checkGradientVisibility,
    init,
    submitProgressForm,
  }
}
