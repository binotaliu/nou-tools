<script setup>
// Purely static content (no ViewModel/props). What varies is the visitor's
// device, which only the browser knows, so it's detected after mount by
// usePwaInstallPage; every panel is still reachable by hand.
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import IosAddToHomeScreen from '../../Components/Pwa/IosAddToHomeScreen.vue'
import IosCompactMenuButton from '../../Components/Pwa/IosCompactMenuButton.vue'
import IosCompactMenuOpen from '../../Components/Pwa/IosCompactMenuOpen.vue'
import IosPageMenuButton from '../../Components/Pwa/IosPageMenuButton.vue'
import IosPageMenuOpen from '../../Components/Pwa/IosPageMenuOpen.vue'
import IosShareSheetCollapsed from '../../Components/Pwa/IosShareSheetCollapsed.vue'
import IosShareSheetExpanded from '../../Components/Pwa/IosShareSheetExpanded.vue'
import IosToolbarShare from '../../Components/Pwa/IosToolbarShare.vue'
import IpadToolbarShare from '../../Components/Pwa/IpadToolbarShare.vue'
import MacSafariFileMenu from '../../Components/Pwa/MacSafariFileMenu.vue'
import usePwaInstallPage from '../../Composables/usePwaInstallPage'

const { platform, canInstall, isInstalled, justInstalled, install } =
  usePwaInstallPage()

const platforms = [
  { key: 'ios', label: 'iPhone / iPad' },
  { key: 'android', label: 'Android' },
  { key: 'mac', label: 'Mac' },
]

// Safari has moved its Share button around a lot, and the OS version can't be
// read from the user agent (it's frozen), so the visitor picks the layout
// that looks like theirs.
const layouts = [
  {
    key: 'ios27',
    title: '網址列在下方，右邊有分頁按鈕',
    hint: 'iPhone・iOS 27',
  },
  {
    key: 'compact',
    title: '網址列在下方，右邊有 [•••]',
    hint: 'iPhone・iOS 26 預設版面',
  },
  {
    key: 'toolbar',
    title: '畫面下方有一排按鈕',
    hint: 'iPhone・iOS 18 及更早，或 iOS 26 及之後的「底部」與「頂部」模式',
  },
  {
    key: 'ipad',
    title: '按鈕都在畫面上方',
    hint: 'iPad',
  },
]
// Newest first, and the default: it's the layout of what's shipping now.
const layout = ref('ios27')

const sectionClass =
  'rounded-xl border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900'
const stepNumberClass =
  'flex size-7 shrink-0 items-center justify-center rounded-full bg-theme-700 text-sm font-semibold text-white dark:bg-theme-600'
const figureClass = 'mx-auto w-full max-w-64'
const captionClass =
  'mt-2 text-center text-xs text-theme-600 dark:text-zinc-400'
const bodyClass = 'text-sm leading-relaxed text-theme-700 dark:text-zinc-300'
</script>

<template>
  <Head title="安裝成 App - NOU 小幫手" />

  <AppLayout>
    <div class="mx-auto max-w-3xl space-y-6">
      <div>
        <h2
          class="text-3xl font-bold tracking-tight text-theme-700 dark:text-zinc-200"
          data-testid="pwa-install-title"
        >
          安裝 NOU 小幫手
        </h2>
        <p class="mt-3 text-theme-600 dark:text-zinc-400">
          NOU 小幫手可以被安裝為 PWA App。安裝為 PWA App
          後，就可以接收推播通知，還能離線檢視課表。
        </p>
      </div>

      <section
        v-if="isInstalled"
        :class="[sectionClass, 'flex items-start gap-3']"
        data-testid="pwa-install-installed"
      >
        <Icon
          name="check-circle"
          class="mt-0.5 size-6 shrink-0 text-theme-700 dark:text-theme-400"
        />
        <p :class="bodyClass">
          你現在就是在已安裝的 App 裡開啟這個網頁，不需要再次安裝。
          不過，如果你想瞭解如何手動安裝，或是想要在其他裝置上安裝，也可以繼續看下面的說明。
        </p>
      </section>

      <div
        class="flex gap-1 rounded-lg bg-theme-100 p-1 dark:bg-zinc-800"
        role="tablist"
        aria-label="選擇你的裝置"
      >
        <button
          v-for="option in platforms"
          :id="`pwa-install-tab-${option.key}`"
          :key="option.key"
          type="button"
          role="tab"
          class="flex-1 rounded-md px-3 py-2 text-sm font-medium transition focus:ring-2 focus:ring-theme-500 focus:outline-none"
          :class="
            platform === option.key
              ? 'bg-white text-theme-800 shadow-sm dark:bg-zinc-900 dark:text-zinc-100'
              : 'text-theme-600 hover:text-theme-800 dark:text-zinc-400 dark:hover:text-zinc-200'
          "
          :aria-selected="platform === option.key"
          :aria-controls="`pwa-install-panel-${option.key}`"
          :data-testid="`pwa-install-tab-${option.key}`"
          @click="platform = option.key"
        >
          {{ option.label }}
        </button>
      </div>

      <!-- Android (and anything else that offers a one-tap install) -->
      <section
        v-if="platform === 'android'"
        id="pwa-install-panel-android"
        role="tabpanel"
        aria-labelledby="pwa-install-tab-android"
        :class="[sectionClass, 'space-y-4']"
        data-testid="pwa-install-android"
      >
        <template v-if="justInstalled">
          <p :class="bodyClass" data-testid="pwa-install-done">
            安裝完成！之後請從主畫面（或應用程式列表）的「NOU 小幫手」圖示開啟。
          </p>
        </template>
        <template v-else-if="canInstall">
          <p :class="bodyClass">
            你的瀏覽器可以直接安裝。請按一下下面的按鈕，再於跳出的視窗點選
            [安裝] 即可。
          </p>
          <button
            type="button"
            class="inline-flex items-center justify-center gap-2 rounded-md bg-theme-700 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-theme-600 focus:ring-2 focus:ring-theme-500 focus:outline-none"
            data-testid="pwa-install-button"
            @click="install()"
          >
            <Icon name="device-phone-mobile" class="size-5" />
            安裝為 App
          </button>
        </template>
        <template v-else>
          <p :class="bodyClass" data-testid="pwa-install-android-manual">
            目前這個瀏覽器未提供直接安裝的功能，可以手動加入：
          </p>
          <ol :class="[bodyClass, 'list-decimal space-y-2 pl-5']">
            <li>
              用 Chrome 開啟這個網頁。其他瀏覽器（例如 Edge、Samsung
              Internet）也可以，但按鈕位置可能不同。
            </li>
            <li>點右上角的 [⋮] 選單。</li>
            <li>選擇 [安裝應用程式] 或 [加到主畫面]，再依提示按 [安裝]。</li>
          </ol>
          <p class="text-xs text-theme-500 dark:text-zinc-400">
            如果你使用 iPhone 或 iPad，請切換到上方的「iPhone / iPad」。
          </p>
        </template>
      </section>

      <!-- Mac: Chrome installs like Android; Safari uses the Dock -->
      <div
        v-else-if="platform === 'mac'"
        id="pwa-install-panel-mac"
        role="tabpanel"
        aria-labelledby="pwa-install-tab-mac"
        class="space-y-6"
        data-testid="pwa-install-mac"
      >
        <section :class="[sectionClass, 'space-y-4']">
          <h3 class="text-lg font-semibold text-theme-800 dark:text-zinc-100">
            Chrome
          </h3>
          <template v-if="justInstalled">
            <p :class="bodyClass" data-testid="pwa-install-done">
              安裝完成！之後可以從「Launchpad」或 Dock 裡的「NOU
              小幫手」圖示開啟。
            </p>
          </template>
          <template v-else-if="canInstall">
            <p :class="bodyClass">
              你的瀏覽器可以直接安裝。請按一下下方按鈕，再於跳出的視窗點選
              [安裝] 即可。
            </p>
            <button
              type="button"
              class="inline-flex items-center justify-center gap-2 rounded-md bg-theme-700 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-theme-600 focus:ring-2 focus:ring-theme-500 focus:outline-none"
              data-testid="pwa-install-button"
              @click="install()"
            >
              <Icon name="device-phone-mobile" class="size-5" />
              安裝為 App
            </button>
          </template>
          <template v-else>
            <p :class="bodyClass" data-testid="pwa-install-mac-chrome-manual">
              用 Chrome 開啟這個網頁，可以手動安裝：
            </p>
            <ol :class="[bodyClass, 'list-decimal space-y-2 pl-5']">
              <li>
                點一下網址列最右邊的「安裝」圖示（螢幕加向下箭頭），再按
                [安裝]。
              </li>
              <li>
                看不到圖示時，點選右上角的 [⋮] 選單，選擇 [投放、儲存及分享]
                裡的 [安裝 NOU 小幫手]。
              </li>
            </ol>
          </template>
        </section>

        <section
          :class="[sectionClass, 'space-y-4']"
          data-testid="pwa-install-mac-safari"
        >
          <h3 class="text-lg font-semibold text-theme-800 dark:text-zinc-100">
            Safari
          </h3>
          <p :class="bodyClass">
            Safari 可從選單加到 Dock（macOS Sonoma 14 以上）：
          </p>
          <ol :class="[bodyClass, 'list-decimal space-y-2 pl-5']">
            <li>用 Safari 開啟這個網頁。</li>
            <li>點選螢幕上方選單列的 [檔案]。</li>
            <li>選擇 [新增到 Dock⋯]。</li>
            <li>接著點擊 [加入]。之後 Dock 裡就會出現「NOU 小幫手」的圖示。</li>
          </ol>
          <figure>
            <div class="mx-auto w-full max-w-md"><MacSafariFileMenu /></div>
            <figcaption :class="captionClass">
              在 [檔案] 選單中，點選 [新增到 Dock⋯]
            </figcaption>
          </figure>
        </section>
      </div>

      <!-- iPhone / iPad -->
      <div
        v-else
        id="pwa-install-panel-ios"
        role="tabpanel"
        aria-labelledby="pwa-install-tab-ios"
        class="space-y-6"
        data-testid="pwa-install-ios"
      >
        <section :class="[sectionClass, 'flex items-start gap-3']">
          <span :class="stepNumberClass">1</span>
          <div class="space-y-2">
            <h3 class="text-lg font-semibold text-theme-800 dark:text-zinc-100">
              用 Safari 開啟本站
            </h3>
            <p :class="bodyClass">
              只有在瀏覽器中，才可以加入主畫面。若你是在 LINE、Facebook 等 App
              內開啟的，請先按一下右上或右下角的 [⋮] ，然後選擇 [在瀏覽器中開啟]
              以 Safari 開啟；Chrome、Edge 等瀏覽器（iOS 16.4
              以上）也可以，只是按鈕位置與 Safari 不同。
            </p>
          </div>
        </section>

        <section :class="[sectionClass, 'space-y-5']">
          <div class="flex items-start gap-3">
            <span :class="stepNumberClass">2</span>
            <div class="space-y-1">
              <h3
                class="text-lg font-semibold text-theme-800 dark:text-zinc-100"
              >
                找到「分享」按鈕
              </h3>
              <p :class="bodyClass">
                Safari
                有各種版面配置，請從下方四個版面配置中找到與你的畫面最像的：
              </p>
            </div>
          </div>

          <fieldset class="grid gap-2 sm:grid-cols-2">
            <legend class="sr-only">Safari 版面</legend>
            <label
              v-for="option in layouts"
              :key="option.key"
              class="cursor-pointer rounded-lg border p-3 transition has-focus-visible:ring-2 has-focus-visible:ring-theme-500"
              :class="
                layout === option.key
                  ? 'border-theme-600 bg-theme-50 dark:border-theme-400 dark:bg-zinc-800'
                  : 'border-theme-200 hover:border-theme-400 dark:border-zinc-700 dark:hover:border-zinc-500'
              "
              :data-testid="`pwa-install-layout-${option.key}`"
            >
              <input
                v-model="layout"
                type="radio"
                name="safari-layout"
                class="sr-only"
                :value="option.key"
              />
              <span
                class="block text-sm font-medium text-theme-800 dark:text-zinc-100"
              >
                {{ option.title }}
              </span>
              <span
                class="mt-1 block text-xs text-theme-600 dark:text-zinc-400"
              >
                {{ option.hint }}
              </span>
            </label>
          </fieldset>

          <div v-if="layout === 'ios27'" data-testid="pwa-install-guide-ios27">
            <p :class="bodyClass">
              先點網址列裡最左邊的小圖示，再點跳出選單中的「分享」圖示（方框加向上箭頭）。選單內容會依網頁與你安裝的延伸功能而不同，通常在最上方的前幾個選項。
            </p>
            <div class="mt-4 grid grid-cols-2 gap-4">
              <figure>
                <div :class="figureClass"><IosPageMenuButton /></div>
                <figcaption :class="captionClass">
                  ① 點網址列裡的圖示
                </figcaption>
              </figure>
              <figure>
                <div :class="figureClass"><IosPageMenuOpen /></div>
                <figcaption :class="captionClass">② 點「分享」</figcaption>
              </figure>
            </div>
          </div>

          <div
            v-else-if="layout === 'compact'"
            data-testid="pwa-install-guide-compact"
          >
            <p :class="bodyClass">
              先點右下角的「•••」，再點選單最上方的「分享」。
            </p>
            <div class="mt-4 grid grid-cols-2 gap-4">
              <figure>
                <div :class="figureClass"><IosCompactMenuButton /></div>
                <figcaption :class="captionClass">① 按一下「•••」</figcaption>
              </figure>
              <figure>
                <div :class="figureClass"><IosCompactMenuOpen /></div>
                <figcaption :class="captionClass">② 按一下「分享」</figcaption>
              </figure>
            </div>
          </div>

          <div
            v-else-if="layout === 'toolbar'"
            data-testid="pwa-install-guide-toolbar"
          >
            <p :class="bodyClass">
              按一下畫面下方工具列中間的「分享」圖示（方框加向上箭頭）。
            </p>
            <figure class="mt-4">
              <div :class="figureClass"><IosToolbarShare /></div>
            </figure>
            <p class="mt-3 text-xs text-theme-500 dark:text-zinc-400">
              往下捲動網頁時工具列會收起來，往上輕滑一下或點畫面最下緣就會再出現。
            </p>
          </div>

          <div v-else data-testid="pwa-install-guide-ipad">
            <p :class="bodyClass">
              按一下畫面右上角的「分享」圖示（方框加向上箭頭），在「+」的左邊。視窗比較窄時，可能在「•••」選單裡。
            </p>
            <figure class="mt-4">
              <div class="mx-auto w-full max-w-md">
                <IpadToolbarShare />
              </div>
            </figure>
          </div>
        </section>

        <section :class="[sectionClass, 'space-y-4']">
          <div class="flex items-start gap-3">
            <span :class="stepNumberClass">3</span>
            <div class="space-y-1">
              <h3
                class="text-lg font-semibold text-theme-800 dark:text-zinc-100"
              >
                選擇「加入主畫面」
              </h3>
              <p :class="bodyClass">
                在跳出的分享選單裡，按一下最右邊的 [檢視較多] 展開清單，再點選
                [加入主畫面]。如果沒有看到「檢視較多」，請直接往上滑動清單，應該就可看到
                [加入主畫面]。
              </p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <figure>
              <div :class="figureClass"><IosShareSheetCollapsed /></div>
              <figcaption :class="captionClass">① 按一下 [檢視較多]</figcaption>
            </figure>
            <figure>
              <div :class="figureClass"><IosShareSheetExpanded /></div>
              <figcaption :class="captionClass">
                ② 按一下 [加入主畫面]
              </figcaption>
            </figure>
          </div>
        </section>

        <section :class="[sectionClass, 'space-y-4']">
          <div class="flex items-start gap-3">
            <span :class="stepNumberClass">4</span>
            <div class="space-y-1">
              <h3
                class="text-lg font-semibold text-theme-800 dark:text-zinc-100"
              >
                按右上角的「加入」
              </h3>
              <p :class="bodyClass">
                名稱可以照預設。iOS 26 之後這裡會多一個「以網頁 App
                開啟」的開關，請保持開啟，這樣才會像 App
                一樣全螢幕開啟。完成後主畫面就會出現「NOU 小幫手」的圖示。
              </p>
            </div>
          </div>
          <figure>
            <div :class="figureClass"><IosAddToHomeScreen /></div>
          </figure>
        </section>
      </div>
    </div>
  </AppLayout>
</template>
