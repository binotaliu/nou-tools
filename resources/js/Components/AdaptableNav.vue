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

const toggleClass =
  'inline-flex items-center justify-center rounded-md border border-theme-200 bg-white p-2 text-theme-700 transition hover:bg-theme-50 focus:ring-2 focus:ring-theme-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800'
</script>

<template>
  <div class="hidden print:hidden wide-pwa:contents">
    <!-- Top tab bar -->
    <header
      class="sticky top-0 z-40 box-content h-12 border-b border-theme-200 bg-white pt-[env(safe-area-inset-top)] dark:border-zinc-700 dark:bg-zinc-900 pwa-sidebar:hidden"
      data-testid="adaptable-nav-tabs"
    >
      <div
        class="mx-auto flex h-full max-w-7xl items-center justify-between gap-2 px-3 md:px-6"
      >
        <nav
          aria-label="主要導覽"
          data-testid="adaptable-nav-tabs-list"
          class="flex min-w-0 items-center gap-1"
        >
          <Link
            v-for="item in primary"
            :key="item.href"
            :href="item.href"
            :aria-current="item.active ? 'page' : null"
            class="inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-sm font-medium whitespace-nowrap transition-colors"
            :class="itemClass(item.active)"
          >
            <Icon :name="item.icon" class="size-4 shrink-0" />
            <span>{{ item.label }}</span>
          </Link>

          <div ref="moreRoot" class="relative">
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-sm font-medium whitespace-nowrap transition-colors"
              :class="itemClass(moreActive())"
              :aria-expanded="moreOpen.toString()"
              aria-controls="adaptable-nav-more-menu"
              data-testid="adaptable-nav-more"
              @click="moreOpen = !moreOpen"
            >
              <span>更多</span>
              <Icon
                name="chevron-down"
                class="size-4 shrink-0 transition-transform"
                :class="moreOpen ? 'rotate-180' : ''"
              />
            </button>

            <div
              v-show="moreOpen"
              id="adaptable-nav-more-menu"
              class="absolute top-full left-0 z-10 mt-2 w-60 space-y-1 rounded-md border border-theme-200 bg-white p-2 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
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
        </nav>

        <div class="flex shrink-0 items-center gap-2">
          <ThemeSwitcherPopover
            :accesskey="navStyle === 'tabs' ? '3' : undefined"
          />
          <button
            type="button"
            :class="toggleClass"
            data-testid="nav-style-to-sidebar"
            @click="setNavStyle('sidebar')"
          >
            <span class="sr-only">切換為側邊欄</span>
            <Icon name="view-columns" class="size-5" />
          </button>
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
          :accesskey="navStyle === 'sidebar' ? '3' : undefined"
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
