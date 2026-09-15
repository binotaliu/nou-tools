import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

// Vue port of the `nouDatePicker` Alpine.data() component
// (resources/js/alpine-components.js). `initial` mirrors { date }.
export default function useDatePicker(initial) {
  const date = ref(initial.date)

  function navigate() {
    router.visit(`?date=${date.value}`)
  }

  return { date, navigate }
}
