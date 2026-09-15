import { reactive } from 'vue'

// Vue port of the `nouAnnouncementFilter` Alpine.data() component
// (resources/js/alpine-components.js). `initial` mirrors
// { sourceCategories, selected }.
export default function useAnnouncementFilter(initial) {
  const state = reactive({
    sourceCategories: initial.sourceCategories,
    selected: initial.selected,
    openSources: {},
  })

  function categoriesFor(source) {
    return state.sourceCategories[source] ?? []
  }

  function selectedFor(source) {
    return state.selected[source] ?? []
  }

  function isCategoryChecked(source, category) {
    return selectedFor(source).includes(category)
  }

  function isSourceChecked(source) {
    const total = categoriesFor(source).length
    return total > 0 && selectedFor(source).length === total
  }

  function isSourceIndeterminate(source) {
    const selectedCount = selectedFor(source).length
    return selectedCount > 0 && !isSourceChecked(source)
  }

  function isSourceExpanded(source) {
    return state.openSources[source] ?? false
  }

  function toggleSourceExpansion(source) {
    state.openSources[source] = !isSourceExpanded(source)
  }

  function toggleSource(source, checked) {
    if (checked) {
      state.selected[source] = [...categoriesFor(source)]
      return
    }

    delete state.selected[source]
  }

  function toggleCategory(source, category, checked) {
    const selectedCategories = [...selectedFor(source)]

    if (checked && !selectedCategories.includes(category)) {
      selectedCategories.push(category)
    }

    if (!checked) {
      const index = selectedCategories.indexOf(category)

      if (index !== -1) {
        selectedCategories.splice(index, 1)
      }
    }

    if (selectedCategories.length === 0) {
      delete state.selected[source]
      return
    }

    state.selected[source] = selectedCategories
  }

  function selectedCategoryCount() {
    return Object.values(state.selected).reduce(
      (sum, categories) => sum + categories.length,
      0
    )
  }

  function selectedSourceCount() {
    return Object.keys(state.selected).length
  }

  return {
    state,
    categoriesFor,
    selectedFor,
    isCategoryChecked,
    isSourceChecked,
    isSourceIndeterminate,
    isSourceExpanded,
    toggleSourceExpansion,
    toggleSource,
    toggleCategory,
    selectedCategoryCount,
    selectedSourceCount,
  }
}
