<script setup>
// Purely static content (no ViewModel/props). Every accesskey row and every
// modifier row is always rendered so the page works without JS; the platform
// detection only highlights the row that applies to the viewer.
import { onMounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

const globalKeys = [
  { key: '0', target: '無障礙說明（本頁）' },
  { key: '1', target: '首頁' },
  { key: '2', target: '跳到主要區塊' },
  { key: '3', target: '我的課表' },
  { key: '9', target: '外觀與文字大小設定' },
]

const scheduleKeys = [
  { key: '4', target: '下一堂課：移到下一堂課的資訊' },
  { key: '5', target: '進入教室：開啟下一堂課的視訊上課連結' },
  { key: '6', target: '完整課表：移到課程清單' },
  { key: '7', target: '選擇學期' },
]

const searchKeys = [
  { key: '8', target: '搜尋欄（本學期開課表、優惠店家、建立與編輯課表）' },
]

const modifiers = [
  {
    id: 'chrome',
    browser: 'Chrome、Edge（Windows / Linux）',
    keys: 'Alt + 數字',
  },
  {
    id: 'firefox',
    browser: 'Firefox（Windows / Linux）',
    keys: 'Alt + Shift + 數字',
  },
  {
    id: 'mac',
    browser: 'Chrome、Firefox、Safari（macOS）',
    keys: 'Control + Option + 數字',
  },
]

const detected = ref(null)

onMounted(() => {
  const platform = navigator.userAgentData?.platform ?? navigator.platform ?? ''
  const ua = navigator.userAgent

  if (/mac/i.test(platform)) {
    detected.value = 'mac'
  } else if (/firefox/i.test(ua)) {
    detected.value = 'firefox'
  } else {
    detected.value = 'chrome'
  }
})
</script>

<template>
  <Head title="無障礙說明 - NOU 小幫手" />

  <AppLayout>
    <div class="mx-auto max-w-3xl space-y-8">
      <div>
        <h2
          class="text-3xl font-bold tracking-tight text-theme-700 dark:text-zinc-200"
          data-testid="accessibility-title"
        >
          無障礙說明
        </h2>
        <p class="mt-3 text-theme-700 dark:text-zinc-400">
          這裡說明 NOU
          小幫手的快速鍵、鍵盤操作與顯示設定，方便使用鍵盤或螢幕閱讀器的同學。
        </p>
      </div>

      <section
        class="rounded-xl border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        data-testid="accessibility-accesskeys"
        aria-labelledby="accesskeys-heading"
      >
        <h3
          id="accesskeys-heading"
          class="text-lg font-semibold text-theme-800 dark:text-zinc-100"
        >
          快速鍵
        </h3>
        <p class="mt-3 text-sm text-theme-700 dark:text-zinc-300">
          按下「修飾鍵 + 數字」即可直接跳到對應的位置。不同瀏覽器的修飾鍵不同：
        </p>

        <table
          class="mt-3 w-full text-left text-sm"
          data-testid="accessibility-modifiers"
        >
          <caption class="sr-only">
            各瀏覽器的快速鍵修飾鍵
          </caption>
          <thead>
            <tr class="text-theme-700 dark:text-zinc-400">
              <th scope="col" class="py-1 pr-4 font-medium">瀏覽器</th>
              <th scope="col" class="py-1 font-medium">按法</th>
            </tr>
          </thead>
          <tbody class="text-theme-900 dark:text-zinc-100">
            <tr
              v-for="row in modifiers"
              :key="row.id"
              :data-current="detected === row.id ? 'true' : null"
              class="border-t border-theme-100 data-[current=true]:font-semibold dark:border-zinc-800"
            >
              <th scope="row" class="py-2 pr-4 font-normal">
                {{ row.browser }}
                <span v-if="detected === row.id" class="ml-1 text-xs"
                  >（你目前使用的）</span
                >
              </th>
              <td class="py-2 font-mono">{{ row.keys }}</td>
            </tr>
          </tbody>
        </table>

        <h4 class="mt-6 font-semibold text-theme-800 dark:text-zinc-100">
          每一頁都能用
        </h4>
        <table
          class="mt-2 w-full text-left text-sm"
          data-testid="accessibility-global-keys"
        >
          <caption class="sr-only">
            全站快速鍵
          </caption>
          <thead class="sr-only">
            <tr>
              <th scope="col">數字</th>
              <th scope="col">位置</th>
            </tr>
          </thead>
          <tbody class="text-theme-900 dark:text-zinc-100">
            <tr
              v-for="row in globalKeys"
              :key="row.key"
              class="border-t border-theme-100 dark:border-zinc-800"
            >
              <th scope="row" class="w-12 py-2 pr-4 font-mono font-semibold">
                {{ row.key }}
              </th>
              <td class="py-2">{{ row.target }}</td>
            </tr>
          </tbody>
        </table>

        <h4 class="mt-6 font-semibold text-theme-800 dark:text-zinc-100">
          各頁專用（4 至 8）
        </h4>
        <p class="mt-2 text-sm text-theme-700 dark:text-zinc-300">
          數字 4 到 8 依目前的頁面而定，沒有對應內容的頁面就沒有該快速鍵。
        </p>

        <h5
          class="mt-4 text-sm font-semibold text-theme-800 dark:text-zinc-100"
        >
          我的課表頁面
        </h5>
        <table
          class="mt-2 w-full text-left text-sm"
          data-testid="accessibility-schedule-keys"
        >
          <caption class="sr-only">
            我的課表頁面的快速鍵
          </caption>
          <thead class="sr-only">
            <tr>
              <th scope="col">數字</th>
              <th scope="col">位置</th>
            </tr>
          </thead>
          <tbody class="text-theme-900 dark:text-zinc-100">
            <tr
              v-for="row in scheduleKeys"
              :key="row.key"
              class="border-t border-theme-100 dark:border-zinc-800"
            >
              <th scope="row" class="w-12 py-2 pr-4 font-mono font-semibold">
                {{ row.key }}
              </th>
              <td class="py-2">{{ row.target }}</td>
            </tr>
          </tbody>
        </table>

        <h5
          class="mt-4 text-sm font-semibold text-theme-800 dark:text-zinc-100"
        >
          有搜尋欄的頁面
        </h5>
        <table
          class="mt-2 w-full text-left text-sm"
          data-testid="accessibility-search-keys"
        >
          <caption class="sr-only">
            搜尋快速鍵
          </caption>
          <thead class="sr-only">
            <tr>
              <th scope="col">數字</th>
              <th scope="col">位置</th>
            </tr>
          </thead>
          <tbody class="text-theme-900 dark:text-zinc-100">
            <tr
              v-for="row in searchKeys"
              :key="row.key"
              class="border-t border-theme-100 dark:border-zinc-800"
            >
              <th scope="row" class="w-12 py-2 pr-4 font-mono font-semibold">
                {{ row.key }}
              </th>
              <td class="py-2">{{ row.target }}</td>
            </tr>
          </tbody>
        </table>

        <p class="mt-4 text-xs text-theme-700 dark:text-zinc-400">
          螢幕較窄時（手機或小平板）頂端選單會收起來，數字 3
          要先打開選單才能使用；0、1、2、9
          在任何寬度都可以用。以「加到主畫面」方式安裝的手機版不提供全站快速鍵。
        </p>
      </section>

      <section
        class="rounded-xl border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        data-testid="accessibility-keyboard"
        aria-labelledby="keyboard-heading"
      >
        <h3
          id="keyboard-heading"
          class="text-lg font-semibold text-theme-800 dark:text-zinc-100"
        >
          鍵盤操作
        </h3>
        <ul
          class="mt-3 list-disc space-y-2 pl-5 text-sm text-theme-700 dark:text-zinc-300"
        >
          <li>
            每一頁按第一次 Tab 會出現「跳到主要區塊」，第二次 Tab
            會出現「無障礙說明」。
          </li>
          <li>選單、彈出視窗與對話框都可以按 Esc 關閉。</li>
          <li>
            自習室的每個座位都是按鈕，可用 Tab 移動、Enter
            或空白鍵入座；座位名稱會說明樓層、桌號與是否有人。
          </li>
        </ul>
      </section>

      <section
        class="rounded-xl border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        data-testid="accessibility-display"
        aria-labelledby="display-heading"
      >
        <h3
          id="display-heading"
          class="text-lg font-semibold text-theme-800 dark:text-zinc-100"
        >
          顯示與閱讀
        </h3>
        <p class="mt-3 text-sm text-theme-700 dark:text-zinc-300">
          你可以在
          <Link
            href="/settings"
            class="font-medium text-theme-700 underline underline-offset-4 hover:no-underline dark:text-zinc-300"
          >
            設定
          </Link>
          調整淺色、深色主題、強調色與四種文字大小。本站會遵循系統的「減少動態效果」設定。
        </p>
      </section>

      <section
        class="rounded-xl border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        data-testid="accessibility-structure"
        aria-labelledby="structure-heading"
      >
        <h3
          id="structure-heading"
          class="text-lg font-semibold text-theme-800 dark:text-zinc-100"
        >
          頁面結構與螢幕閱讀器
        </h3>
        <p class="mt-3 text-sm text-theme-700 dark:text-zinc-300">
          每一頁都有頁首、主要導覽、主要內容與頁尾等地標。第一層標題（h1）固定是網站名稱，該頁的標題是第二層標題（h2），使用螢幕閱讀器的標題導覽即可在頁面之間快速定位。
        </p>
      </section>

      <section
        class="rounded-xl border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        data-testid="accessibility-limitations"
        aria-labelledby="limitations-heading"
      >
        <h3
          id="limitations-heading"
          class="text-lg font-semibold text-theme-800 dark:text-zinc-100"
        >
          已知限制與回報
        </h3>
        <ul
          class="mt-3 list-disc space-y-2 pl-5 text-sm text-theme-700 dark:text-zinc-300"
        >
          <li>自習室座位按鈕的名稱較長，座位很多時逐一瀏覽會比較花時間。</li>
          <li>Cookie 提示列位於頁尾之後，不在任何地標之內。</li>
          <li>以「加到主畫面」方式安裝的手機版沒有頁首與第一層標題。</li>
        </ul>
        <p class="mt-3 text-sm text-theme-700 dark:text-zinc-300">
          遇到無法使用的地方，歡迎
          <a
            href="mailto:nou-tools-contact@binota.org"
            class="font-medium underline underline-offset-4 hover:no-underline"
            >寄信告訴我們</a
          >。
        </p>
      </section>
    </div>
  </AppLayout>
</template>
