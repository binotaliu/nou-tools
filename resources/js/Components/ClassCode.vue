<script setup>
// Vue port of resources/views/components/class-code.blade.php. Renders a
// class code badge, showing "統一面授" instead of the raw code for the
// synthetic "ZZZ000" class used to represent a unified in-person session.
defineProps({
  code: {
    type: String,
    default: null,
  },
})
</script>

<template>
  <span
    class="inline-block rounded bg-warm-100 px-2 py-1 font-mono text-xs font-normal text-warm-800 dark:bg-zinc-800 dark:text-zinc-200 print:bg-transparent print:p-0"
  >
    <template v-if="code === 'ZZZ000'">統一面授</template>
    <template v-else>
      <span class="sr-only">班級代碼：</span>
      {{ code }}
    </template>
  </span>
</template>
