// Alpine.data() components for x-data blocks that need arrow functions or
// touch document/window directly — both restricted inside the inline
// directive expressions parsed by the Alpine CSP build's evaluator, but
// fine here since this is ordinary JS executed as a component factory.
export default function registerAlpineComponents(Alpine) {
  Alpine.data('nouThemeSwitcher', () => ({
    theme: localStorage.getItem('theme') || 'system',

    apply() {
      const prefersDark = window.matchMedia(
        '(prefers-color-scheme: dark)'
      ).matches
      const isDark =
        this.theme === 'dark' || (this.theme === 'system' && prefersDark)
      document.documentElement.classList.toggle('dark', isDark)
    },

    cycle() {
      this.theme =
        this.theme === 'system'
          ? 'light'
          : this.theme === 'light'
            ? 'dark'
            : 'system'
      localStorage.setItem('theme', this.theme)
      this.apply()
    },

    init() {
      this.apply()
      window
        .matchMedia('(prefers-color-scheme: dark)')
        .addEventListener('change', () => {
          if (this.theme === 'system') {
            this.apply()
          }
        })
    },
  }))

  Alpine.data('nouAltUuBanner', () => ({
    storageKey: 'alt_uu_promo_banner_dismissed_v1',
    visible: true,

    init() {
      this.visible = localStorage.getItem(this.storageKey) !== '1'
    },

    dismiss() {
      this.visible = false
      localStorage.setItem(this.storageKey, '1')
    },
  }))

  Alpine.data('nouScheduleCustomize', initial => ({
    links: initial.links,

    addLink() {
      if (this.links.length >= 20) {
        return
      }

      this.links.push({ title: '', url: '' })
    },

    removeLink(index) {
      this.links.splice(index, 1)
    },
  }))

  Alpine.data('nouNotification', () => ({
    show: true,

    init() {
      setTimeout(() => {
        this.show = false
      }, 4000)
    },
  }))

  Alpine.data('nouAnnouncementFilter', initial => ({
    sourceCategories: initial.sourceCategories,
    selected: initial.selected,
    openSources: {},

    categoriesFor(source) {
      return this.sourceCategories[source] ?? []
    },

    selectedFor(source) {
      return this.selected[source] ?? []
    },

    isCategoryChecked(source, category) {
      return this.selectedFor(source).includes(category)
    },

    isSourceChecked(source) {
      const total = this.categoriesFor(source).length
      return total > 0 && this.selectedFor(source).length === total
    },

    isSourceIndeterminate(source) {
      const selectedCount = this.selectedFor(source).length
      return selectedCount > 0 && !this.isSourceChecked(source)
    },

    isSourceExpanded(source) {
      return this.openSources[source] ?? false
    },

    toggleSourceExpansion(source) {
      this.openSources[source] = !this.isSourceExpanded(source)
    },

    toggleSource(source, checked) {
      if (checked) {
        this.selected[source] = [...this.categoriesFor(source)]
        return
      }

      delete this.selected[source]
    },

    toggleCategory(source, category, checked) {
      const selectedCategories = [...this.selectedFor(source)]

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
        delete this.selected[source]
        return
      }

      this.selected[source] = selectedCategories
    },

    selectedCategoryCount() {
      return Object.values(this.selected).reduce(
        (sum, categories) => sum + categories.length,
        0
      )
    },

    selectedSourceCount() {
      return Object.keys(this.selected).length
    },
  }))

  Alpine.data('nouAnnouncementPreferences', initial => ({
    catalog: initial.catalog,
    flatCatalog: initial.flatCatalog,
    selected: initial.selected,
    openGroups: {},
    openSources: {},

    sourcesFor(group) {
      return Object.keys(this.catalog[group] ?? {})
    },

    categoriesFor(source) {
      return this.flatCatalog[source] ?? []
    },

    selectedFor(source) {
      return this.selected[source] ?? []
    },

    isCategoryChecked(source, category) {
      return this.selectedFor(source).includes(category)
    },

    isSourceChecked(source) {
      const total = this.categoriesFor(source).length
      return total > 0 && this.selectedFor(source).length === total
    },

    isSourceIndeterminate(source) {
      const selectedCount = this.selectedFor(source).length
      return selectedCount > 0 && !this.isSourceChecked(source)
    },

    isGroupChecked(group) {
      const sources = this.sourcesFor(group)
      return (
        sources.length > 0 &&
        sources.every(source => this.isSourceChecked(source))
      )
    },

    isGroupIndeterminate(group) {
      if (this.isGroupChecked(group)) {
        return false
      }

      return this.sourcesFor(group).some(
        source => this.selectedFor(source).length > 0
      )
    },

    isSourceExpanded(source) {
      return this.openSources[source] ?? false
    },

    toggleSourceExpansion(source) {
      this.openSources[source] = !this.isSourceExpanded(source)
    },

    isGroupExpanded(group) {
      return this.openGroups[group] ?? true
    },

    toggleGroupExpansion(group) {
      this.openGroups[group] = !this.isGroupExpanded(group)
    },

    toggleSource(source, checked) {
      if (checked) {
        this.selected[source] = [...this.categoriesFor(source)]
        return
      }

      delete this.selected[source]
    },

    toggleGroup(group, checked) {
      this.sourcesFor(group).forEach(source =>
        this.toggleSource(source, checked)
      )
    },

    toggleCategory(source, category, checked) {
      const selectedCategories = [...this.selectedFor(source)]

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
        delete this.selected[source]
        return
      }

      this.selected[source] = selectedCategories
    },
  }))

  Alpine.data('nouArticleShare', initial => ({
    showShareModal: false,
    copied: false,
    shareTitle: initial.shareTitle,
    shareUrl: initial.shareUrl,

    async share() {
      if (navigator.share) {
        try {
          await navigator.share({
            title: this.shareTitle,
            url: this.shareUrl,
          })
        } catch (e) {
          // User cancelled the share sheet; nothing to do.
        }

        return
      }

      this.showShareModal = true
    },

    async copy() {
      try {
        await navigator.clipboard.writeText(this.shareUrl)
      } catch (e) {
        this.$refs.shareInput.select()
        document.execCommand('copy')
      }

      this.copied = true
      setTimeout(() => {
        this.copied = false
      }, 2000)
    },
  }))

  Alpine.data('nouCopyLink', initial => ({
    shareUrl: initial.shareUrl,
    copied: false,

    async copy() {
      try {
        await navigator.clipboard.writeText(this.shareUrl)
      } catch (e) {
        this.$refs.shareInput.select()
        document.execCommand('copy')
      }

      this.copied = true
      setTimeout(() => {
        this.copied = false
      }, 2000)
    },
  }))

  Alpine.data('nouLearningProgress', () => ({
    showHorizontalGradient: false,
    showVerticalGradient: false,
    dirty: false,
    unloadListener: null,

    init() {
      this.checkGradientVisibility()
      // $refs (e.g. progressForm) may not be bound yet on the first pass,
      // since this init() runs before Alpine finishes walking the root's
      // children — re-check once they're guaranteed to be ready.
      this.$nextTick(() => this.checkGradientVisibility())
    },

    checkGradientVisibility() {
      const progressForm = this.$refs.progressForm
      this.showHorizontalGradient =
        progressForm.scrollHeight > progressForm.clientHeight &&
        progressForm.scrollTop + progressForm.clientHeight <
          progressForm.scrollHeight
      this.showVerticalGradient =
        progressForm.scrollWidth > progressForm.clientWidth &&
        progressForm.scrollLeft + progressForm.clientWidth <
          progressForm.scrollWidth
    },

    submitProgressForm() {
      document.getElementById('progress-form').submit()
    },
  }))

  Alpine.data('nouDatePicker', initial => ({
    date: initial.date,

    navigate() {
      window.location = '?date=' + this.date
    },
  }))
}
