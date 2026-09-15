<script setup>
// Vue port of components/notification.blade.php + the `nouNotification`
// Alpine.data() component (resources/js/alpine-components.js). Not wired
// into any page yet (no pages are migrated); ported now so later phases can
// import it directly.
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

const COLOR_BY_TYPE = {
  success: 'text-green-400',
  error: 'text-red-400',
  warning: 'text-yellow-400',
  info: 'text-blue-400',
}

const iconName = ICON_BY_TYPE[props.type] ?? ICON_BY_TYPE.info
const iconColor = COLOR_BY_TYPE[props.type] ?? COLOR_BY_TYPE.info

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
    class="pointer-events-none fixed inset-0 z-50 flex items-end px-4 py-6 sm:items-start sm:p-6"
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
          class="pointer-events-auto z-50 w-full max-w-sm translate-y-0 transform rounded-lg border border-warm-200 bg-white opacity-100 shadow outline-1 -outline-offset-1 outline-white/10 transition duration-300 ease-out sm:translate-x-0 dark:border-zinc-700 dark:bg-zinc-900"
        >
          <div class="p-4">
            <div class="flex items-start">
              <div class="shrink-0">
                <Icon :name="iconName" :class="['size-6', iconColor]" />
              </div>
              <div class="ml-3 w-0 flex-1 pt-0.5">
                <p class="text-sm font-medium text-gray-900 dark:text-zinc-100">
                  {{ message }}
                </p>
                <p
                  v-if="$slots.default"
                  class="mt-1 text-sm text-gray-500 dark:text-zinc-400"
                >
                  <slot />
                </p>
              </div>
              <div class="ml-4 flex shrink-0">
                <button
                  type="button"
                  class="inline-flex rounded-md text-gray-400 hover:text-black focus:outline-2 focus:outline-offset-2 focus:outline-indigo-500 dark:hover:text-white"
                  @click="show = false"
                >
                  <span class="sr-only">Close</span>
                  <Icon name="x-mark" class="size-5" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>
