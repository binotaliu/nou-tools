import { reactive } from 'vue'

// Vue port of the `nouAnnouncementPreferences` Alpine.data() component
// (resources/js/alpine-components.js). `initial` mirrors
// { catalog, flatCatalog, selected }.
export default function useAnnouncementPreferences(initial) {
  const state = reactive({
    catalog: initial.catalog,
    flatCatalog: initial.flatCatalog,
    selected: initial.selected,
    openGroups: {},
    openSources: {},
  })

  function sourcesFor(group) {
    return Object.keys(state.catalog[group] ?? {})
  }

  function categoriesFor(source) {
    return state.flatCatalog[source] ?? []
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

  function isGroupChecked(group) {
    const sources = sourcesFor(group)
    return (
      sources.length > 0 && sources.every(source => isSourceChecked(source))
    )
  }

  function isGroupIndeterminate(group) {
    if (isGroupChecked(group)) {
      return false
    }

    return sourcesFor(group).some(source => selectedFor(source).length > 0)
  }

  function isSourceExpanded(source) {
    return state.openSources[source] ?? false
  }

  function toggleSourceExpansion(source) {
    state.openSources[source] = !isSourceExpanded(source)
  }

  function isGroupExpanded(group) {
    return state.openGroups[group] ?? true
  }

  function toggleGroupExpansion(group) {
    state.openGroups[group] = !isGroupExpanded(group)
  }

  function toggleSource(source, checked) {
    if (checked) {
      state.selected[source] = [...categoriesFor(source)]
      return
    }

    delete state.selected[source]
  }

  function toggleGroup(group, checked) {
    sourcesFor(group).forEach(source => toggleSource(source, checked))
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

  return {
    state,
    sourcesFor,
    categoriesFor,
    selectedFor,
    isCategoryChecked,
    isSourceChecked,
    isSourceIndeterminate,
    isGroupChecked,
    isGroupIndeterminate,
    isSourceExpanded,
    toggleSourceExpansion,
    isGroupExpanded,
    toggleGroupExpansion,
    toggleSource,
    toggleGroup,
    toggleCategory,
  }
}
