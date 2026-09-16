<script setup>
// Vue equivalent of `<x-dynamic-component :component="$category->icon" />`
// for discount store categories, whose icon is an admin-editable string like
// "heroicon-o-building-library" stored in the database (see
// database/seeders/DiscountStoreSeeder.php) rather than a fixed set known at
// build time. Icon.vue's curated set can't cover this, so this component
// resolves any name in the 24px outline set at runtime. Used by both
// DiscountStores/Index.vue and DiscountStores/Show.vue.
import * as OutlineIcons from '@heroicons/vue/24/outline'

const props = defineProps({
  name: {
    type: String,
    required: true,
  },
})

function resolve(name) {
  const slug = name.replace(/^heroicon-o-/, '')
  const pascalCase = slug
    .split('-')
    .map(part => part.charAt(0).toUpperCase() + part.slice(1))
    .join('')

  return OutlineIcons[`${pascalCase}Icon`] ?? OutlineIcons.TagIcon
}
</script>

<template>
  <component :is="resolve(props.name)" aria-hidden="true" />
</template>
