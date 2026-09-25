<script setup>
// Auto-dismissing flash/toast message, rendered site-wide by AppLayout.vue
// from the session's flash bag.
import { onMounted, ref } from 'vue'
import Icon from './Icon.vue'

const props = defineProps({
  type: {
    type: String,
    default: 'info',
  },
  message: {
    type: String,
    required: true,
  },
})

const ICON_BY_TYPE = {
  success: 'check-circle',
  error: 'x-circle',
  warning: 'exclamation-circle',
  info: 'information-circle',
}

const STYLE_BY_TYPE = {
  success: {
    icon: 'text-green-700 dark:text-green-400',
    accent: 'bg-green-700 dark:bg-green-400',
  },
  error: {
    icon: 'text-red-600 dark:text-red-400',
    accent: 'bg-red-600 dark:bg-red-400',
  },
  warning: {
    icon: 'text-amber-700 dark:text-amber-400',
    accent: 'bg-amber-600 dark:bg-amber-400',
  },
  info: {
    icon: 'text-theme-700 dark:text-theme-400',
    accent: 'bg-theme-600 dark:bg-theme-400',
  },
}

const iconName = ICON_BY_TYPE[props.type] ?? ICON_BY_TYPE.info
const style = STYLE_BY_TYPE[props.type] ?? STYLE_BY_TYPE.info

const show = ref(true)

onMounted(() => {
  setTimeout(() => {
    show.value = false
  }, 4000)
})
</script>

<template>
  <div
    aria-live="assertive"
    class="pointer-events-none fixed inset-0 z-50 flex items-end px-4 py-6 sm:items-start sm:p-6 bottom-nav:pb-[calc(var(--pwa-nav-height)+1.5rem)]"
  >
    <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
      <Transition
        enter-active-class="transform transition duration-300 ease-out"
        enter-from-class="translate-y-2 opacity-0 sm:translate-x-2 sm:translate-y-0"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-show="show"
          class="pointer-events-auto relative z-50 w-full max-w-sm overflow-hidden rounded-lg border border-theme-200 bg-theme-50 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
        >
          <span
            aria-hidden="true"
            :class="['absolute inset-y-0 left-0 w-1.5', style.accent]"
          />
          <div class="flex items-start gap-3 py-3 pr-3 pl-5">
            <Icon
              :name="iconName"
              :class="['mt-0.5 size-6 shrink-0', style.icon]"
            />
            <div class="min-w-0 flex-1 pt-0.5">
              <p class="text-sm font-medium text-theme-900 dark:text-zinc-100">
                {{ message }}
              </p>
              <p
                v-if="$slots.default"
                class="mt-1 text-sm text-theme-800 dark:text-zinc-300"
              >
                <slot />
              </p>
            </div>
            <button
              type="button"
              class="-my-1 -mr-1 inline-flex size-8 shrink-0 items-center justify-center rounded-lg text-theme-700 transition hover:bg-theme-100 hover:text-theme-900 focus:outline-2 focus:outline-offset-1 focus:outline-theme-700 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white dark:focus:outline-theme-400"
              @click="show = false"
            >
              <span class="sr-only">關閉</span>
              <Icon name="x-mark" class="size-5" />
            </button>
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>
