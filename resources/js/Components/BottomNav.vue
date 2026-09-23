<script setup>
// Installed-PWA navigation on phones: a native-style bottom tab bar (plus a
// "更多" bottom sheet for the overflow links) that replaces the web-style
// header menu. It renders everywhere but is `hidden` unless `html[data-pwa]`
// is set and the screen is below `md` (the `bottom-nav:` variant in app.css),
// so browser tabs, tablets and desktops never show it and the standalone
// check needs no JS — no flash on cold start.
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import Icon from './Icon.vue'

const props = defineProps({
  // Primary tabs: { href, label, icon, active }.
  tabs: { type: Array, required: true },
  // Overflow links shown in the sheet: { href, label, icon, active,
  // offlineAllow? }.
  moreItems: { type: Array, required: true },
  // Changes whenever Inertia navigates; closes the sheet.
  currentPath: { type: String, required: true },
})

const sheetOpen = ref(false)
const moreActive = computed(() => props.moreItems.some(item => item.active))

function closeSheet() {
  sheetOpen.value = false
}

function onKeydown(event) {
  if (sheetOpen.value && event.key === 'Escape') {
    closeSheet()
  }
}

watch(() => props.currentPath, closeSheet)

onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown))

// The active tab is marked three ways so it doesn't hinge on colour alone: a
// bar along its top edge, the saturated accent colour against a muted dark
// tone, and a bolder label.
const tabClass = active =>
  active
    ? 'font-bold text-theme-700 dark:text-theme-700'
    : 'font-medium text-theme-900/70 dark:text-zinc-300'
</script>

<template>
  <div class="hidden print:hidden bottom-nav:block">
    <Transition
      enter-active-class="transition-opacity duration-200 ease-out"
      enter-from-class="opacity-0"
      leave-active-class="transition-opacity duration-150 ease-in"
      leave-to-class="opacity-0"
    >
      <div
        v-if="sheetOpen"
        class="fixed inset-0 z-40 bg-black/40"
        data-testid="bottom-nav-backdrop"
        @click="closeSheet"
      ></div>
    </Transition>

    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-4 opacity-0"
      leave-active-class="transition duration-150 ease-in"
      leave-to-class="translate-y-4 opacity-0"
    >
      <div
        v-if="sheetOpen"
        id="bottom-nav-sheet"
        class="fixed inset-x-0 bottom-(--pwa-nav-height) z-40 max-h-[70vh] space-y-1 overflow-y-auto rounded-t-2xl border-t border-theme-200 bg-white p-3 shadow-[0_-10px_40px_rgba(0,0,0,0.14)] dark:border-zinc-700 dark:bg-zinc-900"
        role="dialog"
        aria-label="更多"
        data-testid="bottom-nav-sheet"
      >
        <Link
          v-for="item in moreItems"
          :key="item.href"
          :href="item.href"
          :data-offline-allow="item.offlineAllow ? '' : null"
          :aria-current="item.active ? 'page' : null"
          class="flex items-center gap-3 rounded-md px-3 py-3 text-sm font-medium transition-colors"
          :class="
            item.active
              ? 'bg-theme-100 text-theme-900 dark:bg-theme-900/40 dark:text-theme-100'
              : 'text-theme-700 hover:bg-theme-100 hover:text-theme-900 dark:text-zinc-400 dark:hover:bg-theme-900/40 dark:hover:text-theme-100'
          "
          @click="closeSheet"
        >
          <Icon :name="item.icon" class="size-5 shrink-0" />
          {{ item.label }}
        </Link>
      </div>
    </Transition>

    <nav
      class="fixed inset-x-0 bottom-0 z-40 border-t border-theme-200 bg-white pr-[env(safe-area-inset-right)] pb-[env(safe-area-inset-bottom)] pl-[env(safe-area-inset-left)] dark:border-zinc-700 dark:bg-zinc-900"
      aria-label="主要導覽"
      data-testid="bottom-nav"
    >
      <div class="mx-auto grid h-14 max-w-xl grid-cols-5">
        <Link
          v-for="tab in tabs"
          :key="tab.href"
          :href="tab.href"
          :aria-current="tab.active ? 'page' : null"
          class="relative flex flex-col items-center justify-center gap-0.5 text-[0.6875rem] transition-colors"
          :class="tabClass(tab.active)"
        >
          <span
            v-if="tab.active"
            class="absolute inset-x-3 top-0 h-[3px] bg-theme-600 dark:bg-theme-400"
            aria-hidden="true"
            data-testid="bottom-nav-indicator"
          ></span>
          <Icon :name="tab.icon" class="size-6 shrink-0" />
          <span class="max-w-full truncate px-1">{{ tab.label }}</span>
        </Link>

        <button
          type="button"
          class="relative flex flex-col items-center justify-center gap-0.5 text-[0.6875rem] transition-colors"
          :class="tabClass(moreActive || sheetOpen)"
          :aria-expanded="sheetOpen.toString()"
          aria-controls="bottom-nav-sheet"
          data-testid="bottom-nav-more"
          @click="sheetOpen = !sheetOpen"
        >
          <span
            v-if="moreActive || sheetOpen"
            class="absolute inset-x-3 top-0 h-[3px] bg-theme-600 dark:bg-theme-400"
            aria-hidden="true"
            data-testid="bottom-nav-indicator"
          ></span>
          <Icon name="squares-plus" class="size-6 shrink-0" />
          <span>更多</span>
        </button>
      </div>
    </nav>
  </div>
</template>
