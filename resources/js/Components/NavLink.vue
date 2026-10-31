<script setup>
// A navigation entry: an Inertia link, or for `external` items (pages that now
// live on another site) a plain anchor that opens in a new tab.
defineOptions({ inheritAttrs: false })

import { Link } from '@inertiajs/vue3'

defineProps({
  href: { type: String, required: true },
  external: { type: Boolean, default: false },
})
</script>

<template>
  <a
    v-if="external"
    :href="href"
    target="_blank"
    rel="noopener"
    v-bind="$attrs"
  >
    <slot />
    <span class="sr-only">（另開新分頁）</span>
  </a>
  <Link v-else :href="href" v-bind="$attrs"><slot /></Link>
</template>
