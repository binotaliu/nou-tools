import { ref } from 'vue'

// Vue port of the `nouDatePicker` Alpine.data() component
// (resources/js/alpine-components.js). `initial` mirrors { date }.
export default function useDatePicker(initial) {
  const date = ref(initial.date)

  function navigate() {
    window.location = '?date=' + date.value
  }

  return { date, navigate }
}
