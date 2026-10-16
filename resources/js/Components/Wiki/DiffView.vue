<script setup>
// Line diff computed on the server (`WikiDiffLineViewModel`): `equal`,
// `added`, `removed`, and `gap` lines whose text is the number of hidden
// unchanged lines. Signs are spelled out for screen readers, since colour
// alone can't carry the meaning.
defineProps({
  lines: {
    type: Array,
    required: true,
  },
})

const STYLES = {
  added:
    'bg-emerald-50 text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-200',
  removed: 'bg-red-50 text-red-900 dark:bg-red-950/60 dark:text-red-200',
  equal: 'text-theme-800 dark:text-zinc-300',
}

const SIGNS = { added: '+', removed: '−', equal: ' ' }
const SPOKEN = { added: '新增：', removed: '刪除：', equal: '' }
</script>

<template>
  <div
    class="overflow-x-auto rounded-lg border border-theme-200 font-mono text-sm leading-6 dark:border-zinc-700"
    data-testid="wiki-diff"
  >
    <p
      v-if="lines.length === 0"
      class="px-4 py-3 text-theme-700 dark:text-zinc-400"
    >
      內容沒有變更。
    </p>
    <template v-for="(line, index) in lines" :key="index">
      <div
        v-if="line.type === 'gap'"
        class="bg-theme-50 px-4 py-1 text-xs text-theme-700 dark:bg-zinc-800 dark:text-zinc-400"
      >
        ⋯ 略過 {{ line.text }} 行未變更的內容
      </div>
      <div
        v-else
        :class="STYLES[line.type]"
        :data-diff-type="line.type"
        class="flex gap-3 px-4 whitespace-pre-wrap"
      >
        <span aria-hidden="true" class="w-3 shrink-0 select-none">{{
          SIGNS[line.type]
        }}</span>
        <span class="min-w-0 break-words"
          ><span class="sr-only">{{ SPOKEN[line.type] }}</span
          >{{ line.text || ' ' }}</span
        >
      </div>
    </template>
  </div>
</template>
