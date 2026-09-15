import { onMounted, ref } from 'vue'

// Vue port of the `nouPwaStandalone` Alpine.data() component
// (resources/js/alpine-components.js). Pure state: whether the page is
// currently running as an installed PWA (standalone display mode).
export default function usePwaStandalone() {
  const isPwa = ref(false)

  onMounted(() => {
    isPwa.value =
      window.matchMedia('(display-mode: standalone)').matches ||
      window.navigator.standalone === true
  })

  return { isPwa }
}
