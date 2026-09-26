<script setup>
// Purely static content (no ViewModel/props). Every accesskey row and every
// modifier row is always rendered so the page works without JS; the platform
// detection only highlights the row that applies to the viewer.
import { onMounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

const keyGroups = [
  {
    testid: 'accessibility-global-keys',
    title: '每一頁都能用',
    keys: [
      { key: '0', target: '無障礙說明（本頁）' },
      { key: '1', target: '跳到主要區塊' },
      { key: '2', target: '我的課表' },
      { key: '3', target: '外觀與文字大小設定' },
    ],
  },
  {
    testid: 'accessibility-schedule-keys',
    title: '我的課表',
    keys: [
      { key: '4', target: '移到最近的一堂課' },
      { key: '5', target: '進入最近一堂課的教室（沒有連結時無效）' },
      { key: '6', target: '移到完整課表' },
      { key: '7', target: '選擇學期' },
    ],
  },
  {
    testid: 'accessibility-study-room-keys',
    title: '自習室',
    keys: [
      { key: '4', target: '快速入座；已入座時移到你的控制列' },
      { key: '5', target: '開始、暫停或繼續計時（已入座時）' },
      { key: '6', target: '移到你的座位，未入座時移到一樓座位' },
      { key: '7', target: '朗讀目前的計時狀態（已入座時）' },
      { key: '8', target: '全螢幕專注（計時中）' },
    ],
  },
  {
    testid: 'accessibility-search-keys',
    title: '有搜尋欄的頁面',
    keys: [
      {
        key: '8',
        target: '搜尋欄（本學期開課表、優惠店家、建立與編輯課表）',
      },
    ],
  },
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
          用鍵盤或螢幕閱讀器使用 NOU 小幫手時需要知道的事。
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
          按住修飾鍵再按數字，就能直接跳到對應的位置。修飾鍵依瀏覽器而異：
        </p>

        <table
          class="mt-3 w-full text-left text-sm"
          data-testid="accessibility-modifiers"
        >
          <caption class="sr-only">
            各瀏覽器的快速鍵修飾鍵
          </caption>
          <thead class="sr-only">
            <tr>
              <th scope="col">瀏覽器</th>
              <th scope="col">按法</th>
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

        <p class="mt-4 text-sm text-theme-700 dark:text-zinc-300">
          0 到 3 在每一頁都一樣；4 到 8
          依頁面而定，沒有對應內容的頁面就沒有該快速鍵。
        </p>

        <template v-for="group in keyGroups" :key="group.testid">
          <h4 class="mt-5 font-semibold text-theme-800 dark:text-zinc-100">
            {{ group.title }}
          </h4>
          <table
            class="mt-2 w-full text-left text-sm"
            :data-testid="group.testid"
          >
            <caption class="sr-only">
              {{
                group.title
              }}的快速鍵
            </caption>
            <thead class="sr-only">
              <tr>
                <th scope="col">數字</th>
                <th scope="col">位置</th>
              </tr>
            </thead>
            <tbody class="text-theme-900 dark:text-zinc-100">
              <tr
                v-for="row in group.keys"
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
        </template>
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
          鍵盤與螢幕閱讀器
        </h3>
        <ul
          class="mt-3 list-disc space-y-2 pl-5 text-sm text-theme-700 dark:text-zinc-300"
        >
          <li>
            每一頁按第一次 Tab 會出現「跳到主要區塊」，第二次是「無障礙說明」。
          </li>
          <li>選單、彈出視窗與對話框都可以按 Esc 關閉。</li>
          <li>
            每一頁都有頁首、導覽、主要內容與頁尾地標。h1 是網站名稱，該頁標題是
            h2。
          </li>
          <li>切換頁面後會念出新頁面的標題，焦點回到主要內容的開頭。</li>
          <li>
            成功訊息會在讀完所需的時間後消失，滑鼠移上去或焦點停在訊息內時會暫停。錯誤訊息會立即念出，並留著直到你按「關閉」。
          </li>
          <li>
            表單有欄位填錯時，錯誤說明會立即念出，焦點會移到第一個有問題的欄位。
          </li>
        </ul>
      </section>

      <section
        class="rounded-xl border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        data-testid="accessibility-study-room"
        aria-labelledby="study-room-heading"
      >
        <h3
          id="study-room-heading"
          class="text-lg font-semibold text-theme-800 dark:text-zinc-100"
        >
          自習室
        </h3>
        <ul
          class="mt-3 list-disc space-y-2 pl-5 text-sm text-theme-700 dark:text-zinc-300"
        >
          <li>
            每層樓的座位只占一個 Tab 位置。進去後用方向鍵移動，Home、End
            到第一個或最後一個，Enter
            或空白鍵入座。座位名稱會說出暱稱、在讀什麼和剩下幾分鐘。
          </li>
          <li>
            「快速入座」會直接坐進第一個空位。入座後焦點移到控制列，離開座位後回到原本的座位。
          </li>
          <li>「清單」把座位改成一層樓一個表格，可以只看有人或空位的座位。</li>
          <li>
            開始、暫停、休息、時間到與座位被釋放都會自動念出。「語音提示」可另外開啟定時報剩餘時間，以及鄰座、同桌或同樓層有人入座或離開的提示，預設關閉。
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
          在
          <Link
            href="/settings"
            class="font-medium text-theme-700 underline underline-offset-4 hover:no-underline dark:text-zinc-300"
          >
            設定
          </Link>
          可以切換淺色或深色主題、調整強調色與四種文字大小，也能開啟「行距與字距」（加大行高、字距與段落間距）和「減少動態效果」（停止動畫、輪播與平滑捲動）。系統的「減少動態效果」設定會自動套用。
        </p>
        <p
          class="mt-3 text-sm text-theme-700 dark:text-zinc-300"
          data-testid="accessibility-focus"
        >
          用 Tab
          切換時，目前的控制項一律有清楚的外框，不會被頁首或底部導覽列遮住。在
          Windows
          高對比模式（強制色彩）下，外框、目前頁面、座位狀態與已選日期都以系統色彩與邊框標示。
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
          <li>Cookie 提示列位於頁尾之後，不在任何地標之內。</li>
          <li>
            「加到主畫面」安裝的手機版沒有頁首與 h1，只有快速鍵 0、1、2 可用。
          </li>
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
