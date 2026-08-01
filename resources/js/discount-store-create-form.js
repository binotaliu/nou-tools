export default function discountStoreCreateForm(config) {
  return {
    type: config.type ?? '',
    city: config.city ?? '',
    district: config.district ?? '',
    districtsByCity: config.districtsByCity ?? {},

    get districts() {
      return this.districtsByCity[this.city] ?? []
    },

    handleTypeChange() {
      this.city = ''
      this.district = ''
    },

    handleCityChange() {
      this.district = ''
    },
  }
}
