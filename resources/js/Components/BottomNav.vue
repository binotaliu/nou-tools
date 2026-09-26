<script setup>
// Installed-PWA navigation on phones: a native-style bottom tab bar (plus a
// "更多" bottom sheet laying the overflow links out as a launcher grid) that replaces the web-style
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
  // Overflow links shown as launcher tiles in the sheet: { href, label,
  // shortLabel?, icon, active, offlineAllow? }. A tile fits about two short
  // lines, so long labels bring a `shortLabel`.
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
    ? 'font-bold text-theme-700 dark:text-theme-400'
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
        class="fixed inset-x-0 bottom-(--pwa-nav-height) z-40 max-h-[70vh] overflow-y-auto rounded-t-2xl border-t border-theme-200 bg-white px-3 pt-2 pb-4 shadow-[0_-10px_40px_rgba(0,0,0,0.14)] dark:border-zinc-700 dark:bg-zinc-900"
        role="dialog"
        aria-label="更多"
        data-testid="bottom-nav-sheet"
      >
        <div
          class="mx-auto mb-3 h-1 w-10 rounded-full bg-theme-200 dark:bg-zinc-700"
          aria-hidden="true"
        ></div>
        <div class="mx-auto grid max-w-xl grid-cols-4 gap-x-2 gap-y-4">
          <Link
            v-for="item in moreItems"
            :key="item.href"
            :href="item.href"
            :data-offline-allow="item.offlineAllow ? '' : null"
            :aria-current="item.active ? 'page' : null"
            class="group flex flex-col items-center gap-1.5 rounded-xl px-1 py-1.5 text-center text-xs leading-tight transition-colors"
            :class="
              item.active
                ? 'font-bold text-theme-900 dark:text-theme-100'
                : 'font-medium text-theme-900/80 dark:text-zinc-300'
            "
            data-testid="bottom-nav-sheet-item"
            @click="closeSheet"
          >
            <span
              class="flex size-12 items-center justify-center rounded-2xl transition-colors"
              :class="
                item.active
                  ? 'bg-theme-700 text-white dark:bg-theme-700'
                  : 'bg-theme-100 text-theme-700 group-hover:bg-theme-200 dark:bg-zinc-800 dark:text-theme-300 dark:group-hover:bg-zinc-700'
              "
            >
              <Icon :name="item.icon" class="size-6 shrink-0" />
            </span>
            <span class="line-clamp-2 break-all">{{
              item.shortLabel ?? item.label
            }}</span>
          </Link>
        </div>
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
          class="relative flex flex-col items-center justify-center gap-0.5 text-xs transition-colors"
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
          class="relative flex flex-col items-center justify-center gap-0.5 text-xs transition-colors"
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
