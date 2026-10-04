<script setup>
// `filters` is the reactive() result of useCourseFilters().
import { onMounted, onUnmounted, ref } from 'vue'
import Icon from '../Icon.vue'
import Select from '../Select.vue'

defineProps({
  filters: {
    type: Object,
    required: true,
  },
})

// Open/close state for the 學系/學分 multi-select panels.
function useDropdown() {
  const open = ref(false)
  const el = ref(null)

  function handleDocumentClick(event) {
    if (open.value && el.value && !el.value.contains(event.target)) {
      open.value = false
    }
  }

  onMounted(() => document.addEventListener('click', handleDocumentClick))
  onUnmounted(() => document.removeEventListener('click', handleDocumentClick))

  return { open, el }
}

const departmentDropdown = useDropdown()
const creditsDropdown = useDropdown()
</script>

<template>
  <div
    class="mb-6 rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
  >
    <form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent>
      <div>
        <label
          for="search"
          aria-hidden="true"
          class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
        >
          搜尋
        </label>
        <input
          id="search"
          v-model="filters.search"
          type="text"
          name="search"
          aria-label="搜尋"
          accesskey="8"
          placeholder="課程名稱..."
          class="w-full rounded-lg border border-zinc-500 px-3 py-2 text-sm focus:border-theme-300 focus:ring-theme-300 dark:border-zinc-500"
        />
      </div>
      <div>
        <label
          for="groupBy"
          aria-hidden="true"
          class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
        >
          分組方式
        </label>
        <Select
          id="groupBy"
          v-model="filters.groupBy"
          aria-label="分組方式"
          data-testid="group-by-select"
        >
          <option value="exam">考試時間</option>
          <option value="department">學系</option>
          <option value="credits">學分數</option>
        </Select>
      </div>
      <div>
        <label
          aria-hidden="true"
          class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
        >
          學系
        </label>
        <div :ref="el => (departmentDropdown.el.value = el)" class="relative">
          <button
            type="button"
            :aria-label="
              '學系：' +
              (filters.department.length
                ? '已選 ' + filters.department.length + ' 項'
                : '全部學系')
            "
            :aria-expanded="departmentDropdown.open.value ? 'true' : 'false'"
            class="flex w-full items-center justify-between rounded-lg border border-zinc-500 px-3 py-2 text-left text-sm focus:border-theme-300 focus:ring-theme-300 dark:border-zinc-500"
            @click="
              departmentDropdown.open.value = !departmentDropdown.open.value
            "
          >
            <span>
              {{
                filters.department.length
                  ? '已選 ' + filters.department.length + ' 項'
                  : '全部學系'
              }}
            </span>
            <Icon name="chevron-down" class="size-4 text-zinc-400" />
          </button>
          <div
            v-show="departmentDropdown.open.value"
            class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-theme-200 bg-white p-2 shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
          >
            <label
              v-for="departmentOption in filters.departmentOptions"
              :key="departmentOption"
              class="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-theme-50 dark:hover:bg-zinc-700"
            >
              <input
                v-model="filters.department"
                type="checkbox"
                :value="departmentOption"
                class="rounded border-zinc-500 dark:border-zinc-500"
              />
              {{ departmentOption }}
            </label>
          </div>
        </div>
      </div>
      <div>
        <label
          aria-hidden="true"
          class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
        >
          學分
        </label>
        <div :ref="el => (creditsDropdown.el.value = el)" class="relative">
          <button
            type="button"
            :aria-label="
              '學分：' +
              (filters.credits.length
                ? '已選 ' + filters.credits.length + ' 項'
                : '全部學分')
            "
            :aria-expanded="creditsDropdown.open.value ? 'true' : 'false'"
            class="flex w-full items-center justify-between rounded-lg border border-zinc-500 px-3 py-2 text-left text-sm focus:border-theme-300 focus:ring-theme-300 dark:border-zinc-500"
            @click="creditsDropdown.open.value = !creditsDropdown.open.value"
          >
            <span>
              {{
                filters.credits.length
                  ? '已選 ' + filters.credits.length + ' 項'
                  : '全部學分'
              }}
            </span>
            <Icon name="chevron-down" class="size-4 text-zinc-400" />
          </button>
          <div
            v-show="creditsDropdown.open.value"
            class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-theme-200 bg-white p-2 shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
          >
            <label
              v-for="creditOption in filters.creditOptions"
              :key="creditOption"
              class="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-theme-50 dark:hover:bg-zinc-700"
            >
              <input
                v-model="filters.credits"
                type="checkbox"
                :value="String(creditOption)"
                class="rounded border-zinc-500 dark:border-zinc-500"
              />
              {{ creditOption }}
            </label>
          </div>
        </div>
      </div>
      <div class="flex items-end">
        <a
          v-show="filters.hasFilters"
          href="#"
          class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 font-semibold text-theme-900 transition hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
          @click.prevent="filters.clearFilters()"
        >
          清除條件
        </a>
      </div>
    </form>
  </div>
</template>
