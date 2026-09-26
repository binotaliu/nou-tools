<script setup>
// Installed-PWA navigation from `md` up (tablet, desktop), after Apple's
// `sidebarAdaptable`: a top tab bar or a sidebar, switched by the reader
// (useNavStyle). Like BottomNav it always renders and CSS decides what shows:
// nothing unless `html[data-pwa]` is set and the screen is `md` or wider (the
// `wide-pwa:` variant in app.css), and then the tab bar or the sidebar
// depending on `html[data-nav-style]`. Only one is ever displayed, so the
// 主要導覽 landmark stays unique. No brand: the app chrome is just navigation.
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import Icon from './Icon.vue'
import ThemeSwitcherPopover from './ThemeSwitcherPopover.vue'
import useNavStyle from '../Composables/useNavStyle'
import usePwaStandalone from '../Composables/usePwaStandalone'

const props = defineProps({
  // Every group holds { href, label, icon, active, offlineAllow? }.
  // `primary` are the tabs, `more` and `other` the overflow (a dropdown in the
  // tab bar, further sidebar sections).
  primary: { type: Array, required: true },
  more: { type: Array, required: true },
  other: { type: Array, required: true },
  // Changes whenever Inertia navigates; closes the dropdown.
  currentPath: { type: String, required: true },
})

const { navStyle, setNavStyle } = useNavStyle()
// Outside an installed PWA the header's button owns accesskey 3.
const { isPwa } = usePwaStandalone()

const moreOpen = ref(false)
const moreRoot = ref(null)
const moreActive = () => [...props.more, ...props.other].some(i => i.active)

function closeMore() {
  moreOpen.value = false
}

function onDocumentClick(event) {
  if (
    moreOpen.value &&
    moreRoot.value &&
    !moreRoot.value.contains(event.target)
  ) {
    closeMore()
  }
}

function onKeydown(event) {
  if (moreOpen.value && event.key === 'Escape') {
    closeMore()
  }
}

watch(() => props.currentPath, closeMore)

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
  document.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onKeydown)
})

const itemClass = active =>
  active
    ? 'bg-theme-100 text-theme-900 dark:bg-theme-900/40 dark:text-theme-100'
    : 'text-theme-700 hover:bg-theme-100 hover:text-theme-900 dark:text-zinc-400 dark:hover:bg-theme-900/40 dark:hover:text-theme-100'

// Active tab: a solid accent capsule. Inactive tabs are plain text: a native
// tab bar has no hover boxes.
const capsuleClass = active =>
  active
    ? 'bg-theme-600 text-white shadow-sm dark:bg-theme-500'
    : 'text-theme-800 hover:text-theme-900 dark:text-zinc-300 dark:hover:text-zinc-100'

// Borderless icon buttons for the bar's edges.
const flatButtonClass =
  'inline-flex size-11 items-center justify-center rounded-md text-theme-700 transition active:scale-95 hover:bg-black/5 focus:ring-2 focus:ring-theme-500 focus:outline-none dark:text-zinc-300 dark:hover:bg-white/10'

const toggleClass =
  'inline-flex items-center justify-center rounded-md border border-theme-200 bg-white p-2 text-theme-700 transition hover:bg-theme-50 focus:ring-2 focus:ring-theme-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800'
</script>

<template>
  <div class="hidden print:hidden wide-pwa:contents">
    <!-- Top tab bar: a centred rounded tray of tabs between the sidebar toggle and
         the utility buttons, on a translucent bar, like iPadOS. -->
    <header
      class="sticky top-0 z-40 box-content h-14 border-b border-black/5 bg-white/70 pt-[env(safe-area-inset-top)] backdrop-blur-xl dark:border-white/10 dark:bg-zinc-900/70 pwa-sidebar:hidden"
      data-testid="adaptable-nav-tabs"
    >
      <div
        class="mx-auto grid h-full max-w-7xl grid-cols-[1fr_auto_1fr] items-center gap-2 px-3 md:px-6"
      >
        <div class="flex items-center justify-self-start">
          <button
            type="button"
            :class="flatButtonClass"
            data-testid="nav-style-to-sidebar"
            @click="setNavStyle('sidebar')"
          >
            <span class="sr-only">切換為側邊欄</span>
            <Icon name="view-columns" class="size-6" />
          </button>
        </div>

        <nav
          aria-label="主要導覽"
          data-testid="adaptable-nav-tabs-list"
          class="flex min-w-0 items-center gap-0.5 rounded-lg bg-theme-100/70 p-1 dark:bg-zinc-800/70"
        >
          <Link
            v-for="item in primary"
            :key="item.href"
            :href="item.href"
            :aria-current="item.active ? 'page' : null"
            class="inline-flex min-h-9 items-center gap-1.5 rounded-md px-3 text-sm font-medium whitespace-nowrap transition-all active:scale-95 lg:px-4"
            :class="capsuleClass(item.active)"
          >
            <Icon :name="item.icon" class="hidden size-4 shrink-0 lg:block" />
            <span>{{ item.label }}</span>
          </Link>
        </nav>

        <div class="flex items-center justify-self-end">
          <ThemeSwitcherPopover
            flat
            :accesskey="isPwa && navStyle === 'tabs' ? '3' : null"
          />

          <div ref="moreRoot" class="relative">
            <button
              type="button"
              :class="[
                flatButtonClass,
                moreActive() ? 'text-theme-700 dark:text-theme-300' : '',
              ]"
              :aria-expanded="moreOpen.toString()"
              aria-controls="adaptable-nav-more-menu"
              data-testid="adaptable-nav-more"
              @click="moreOpen = !moreOpen"
            >
              <span class="sr-only">更多</span>
              <Icon name="ellipsis-horizontal" class="size-6" />
            </button>

            <div
              v-show="moreOpen"
              id="adaptable-nav-more-menu"
              class="absolute top-full right-0 z-10 mt-2 w-60 space-y-1 rounded-lg border border-theme-200 bg-white p-2 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
            >
              <Link
                v-for="item in [...more, ...other]"
                :key="item.href"
                :href="item.href"
                :data-offline-allow="item.offlineAllow ? '' : null"
                :aria-current="item.active ? 'page' : null"
                class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                :class="itemClass(item.active)"
              >
                <Icon :name="item.icon" class="size-4 shrink-0" />
                {{ item.label }}
              </Link>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-40 hidden w-(--pwa-sidebar-width) flex-col border-r border-theme-200 bg-white pt-[env(safe-area-inset-top)] pb-[env(safe-area-inset-bottom)] dark:border-zinc-700 dark:bg-zinc-900 pwa-sidebar:flex"
      data-testid="adaptable-nav-sidebar"
    >
      <nav
        aria-label="主要導覽"
        data-testid="adaptable-nav-sidebar-list"
        class="flex-1 space-y-6 overflow-y-auto p-3"
      >
        <ul class="space-y-1">
          <li v-for="item in primary" :key="item.href">
            <Link
              :href="item.href"
              :aria-current="item.active ? 'page' : null"
              class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors"
              :class="itemClass(item.active)"
            >
              <Icon :name="item.icon" class="size-5 shrink-0" />
              {{ item.label }}
            </Link>
          </li>
        </ul>

        <div>
          <p
            class="px-3 pb-1 text-xs font-semibold text-theme-700/80 dark:text-zinc-500"
          >
            更多
          </p>
          <ul class="space-y-1">
            <li v-for="item in more" :key="item.href">
              <Link
                :href="item.href"
                :data-offline-allow="item.offlineAllow ? '' : null"
                :aria-current="item.active ? 'page' : null"
                class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                :class="itemClass(item.active)"
              >
                <Icon :name="item.icon" class="size-5 shrink-0" />
                {{ item.label }}
              </Link>
            </li>
          </ul>
        </div>

        <ul
          class="space-y-1 border-t border-theme-200 pt-3 dark:border-zinc-700"
        >
          <li v-for="item in other" :key="item.href">
            <Link
              :href="item.href"
              :aria-current="item.active ? 'page' : null"
              class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors"
              :class="itemClass(item.active)"
            >
              <Icon :name="item.icon" class="size-5 shrink-0" />
              {{ item.label }}
            </Link>
          </li>
        </ul>
      </nav>

      <div
        class="flex items-center justify-between border-t border-theme-200 p-3 dark:border-zinc-700"
      >
        <ThemeSwitcherPopover
          placement="above"
          :accesskey="isPwa && navStyle === 'sidebar' ? '3' : null"
        />
        <button
          type="button"
          :class="toggleClass"
          data-testid="nav-style-to-tabs"
          @click="setNavStyle('tabs')"
        >
          <span class="sr-only">切換為頂端列</span>
          <Icon name="view-columns" class="size-5" />
        </button>
      </div>
    </aside>
  </div>
</template>
