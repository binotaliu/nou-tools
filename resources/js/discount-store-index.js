export default function discountStoreIndex(config) {
  return {
    stores: config.stores,
    search: config.initialSearch ?? '',
    category: config.initialCategory ?? '',
    type: config.initialType ?? '',
    city: config.initialCity ?? '',
    page: 1,
    perPage: 20,

    get normalizedSearch() {
      return this.search.trim().toLowerCase()
    },

    get filteredStoreIndices() {
      return this.stores.reduce((indexes, store, index) => {
        const name = store.name.toLowerCase()
        const matchesSearch =
          this.normalizedSearch === '' || name.includes(this.normalizedSearch)
        const matchesCategory =
          this.category === '' ||
          String(store.categoryId) === String(this.category)
        const matchesType = this.type === '' || store.type === this.type
        const matchesCity = this.city === '' || store.city === this.city

        if (matchesSearch && matchesCategory && matchesType && matchesCity) {
          indexes.push(index)
        }

        return indexes
      }, [])
    },

    get visibleStoreIndices() {
      const pageStart = (this.page - 1) * this.perPage
      return this.filteredStoreIndices.slice(
        pageStart,
        pageStart + this.perPage
      )
    },

    get totalPages() {
      return Math.max(
        1,
        Math.ceil(this.filteredStoreIndices.length / this.perPage)
      )
    },

    get hasFilters() {
      return this.search || this.category || this.type || this.city
    },

    applyFilters() {
      this.page = 1
    },

    clearFilters() {
      this.search = ''
      this.category = ''
      this.type = ''
      this.city = ''
      this.page = 1
    },

    goToPage(page) {
      this.page = Math.min(this.totalPages, Math.max(1, page))
    },

    isStoreVisible(storeIndex) {
      return this.visibleStoreIndices.includes(Number(storeIndex))
    },
  }
}
