import { ref } from 'vue'

// `initial` mirrors { links }.
export default function useScheduleCustomize(initial) {
  const links = ref(initial.links)

  function addLink() {
    if (links.value.length >= 20) {
      return
    }

    links.value.push({ title: '', url: '' })
  }

  function removeLink(index) {
    links.value.splice(index, 1)
  }

  return { links, addLink, removeLink }
}
