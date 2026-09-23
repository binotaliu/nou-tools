<script setup>
import Icon from '../Icon.vue'

const steps = [
  {
    icon: 'lock-closed',
    color:
      'bg-sky-500/15 text-sky-800 ring-sky-300/70 dark:bg-sky-500/20 dark:text-sky-400 dark:ring-sky-400/40',
    title: '① 登入時，Alt UU 會安全地連線到空大',
    detail:
      '輸入帳號密碼後，Alt UU 會透過空大官方提供的登入端點完成驗證，並把登入狀態（Cookie / Session 等）安全地存在你的裝置裡。此一過程由你的裝置與空大伺服器直接傳輸，不會有他人經手或介入。',
  },
  {
    icon: 'academic-cap',
    color:
      'bg-amber-500/15 text-amber-800 ring-amber-300/70 dark:bg-amber-500/20 dark:text-amber-400 dark:ring-amber-400/40',
    title: '② 在 Alt UU 裡開啟課程',
    detail:
      '開啟教材時，Alt UU 會直接向空大的伺服器發出請求，就如同在電腦上用網頁瀏覽器打開網頁一樣，只是換了一個 App。',
  },
  {
    icon: 'device-phone-mobile',
    color:
      'bg-violet-500/15 text-violet-800 ring-violet-300/70 dark:bg-violet-500/20 dark:text-violet-400 dark:ring-violet-400/40',
    title: '③ 在手機上將空大回傳的內容組成畫面',
    detail:
      'Alt UU 會在你的裝置上解析、組成空大伺服器回應的網頁、影片、PDF 等內容（跟網頁瀏覽器的原理一樣）。Alt UU 不會把這些教材另外保存或轉送出去。',
  },
]
</script>

<template>
  <div
    class="rounded-lg border border-theme-200 bg-theme-50/60 p-6 dark:border-zinc-700 dark:bg-zinc-800/40"
    data-testid="alt-uu-architecture-illustration"
    aria-hidden="true"
  >
    <!-- Endpoints: device (running Alt UU) <-> school server, direct line -->
    <div
      class="flex flex-col items-stretch gap-0 sm:flex-row sm:items-center sm:justify-center"
    >
      <div
        class="w-full rounded-xl border border-theme-300 bg-white p-4 text-center shadow-sm sm:max-w-52 dark:border-zinc-600 dark:bg-zinc-900"
      >
        <Icon
          name="device-phone-mobile"
          class="mx-auto size-8 text-theme-700 dark:text-zinc-300"
        />
        <p class="mt-2 text-sm font-semibold text-theme-900 dark:text-zinc-100">
          你的手機／裝置
        </p>
        <p
          class="mt-1 rounded-md bg-theme-100 px-2 py-1 text-xs text-theme-700 dark:bg-zinc-800 dark:text-zinc-300"
        >
          Alt UU 在你的裝置上執行
        </p>
      </div>

      <!-- Connector: a visible line with a lock badge, so "direct + secure"
           reads as a picture, not just a caption. -->
      <div
        class="relative flex h-10 items-center justify-center sm:h-auto sm:w-24"
      >
        <div
          class="h-full w-px bg-linear-to-b from-theme-300 via-theme-400 to-theme-300 sm:h-px sm:w-full sm:bg-gradient-to-r dark:from-zinc-600 dark:via-zinc-500 dark:to-zinc-600"
        ></div>
        <div
          class="absolute flex size-9 items-center justify-center rounded-full border border-theme-300 bg-white shadow-sm dark:border-zinc-600 dark:bg-zinc-900"
        >
          <Icon
            name="lock-closed"
            class="size-4 text-theme-700 dark:text-zinc-300"
          />
        </div>
      </div>

      <div
        class="w-full rounded-xl border border-theme-300 bg-white p-4 text-center shadow-sm sm:max-w-52 dark:border-zinc-600 dark:bg-zinc-900"
      >
        <Icon
          name="globe-alt"
          class="mx-auto size-8 text-theme-700 dark:text-zinc-300"
        />
        <p class="mt-2 text-sm font-semibold text-theme-900 dark:text-zinc-100">
          空大伺服器
        </p>
        <p
          class="mt-1 rounded-md bg-theme-100 px-2 py-1 text-xs text-theme-700 dark:bg-zinc-800 dark:text-zinc-300"
        >
          帳號、教材由學校掌控
        </p>
      </div>
    </div>
    <p class="mt-2 text-center text-xs text-theme-700 dark:text-zinc-400">
      端對端直接連線 —— 帳號密碼與教材內容，只會在你的裝置與空大伺服器之間往返
    </p>

    <!-- The 3-step story of a single visit -->
    <ol class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
      <li
        v-for="step in steps"
        :key="step.title"
        class="rounded-xl border border-theme-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div
          class="mb-3 inline-flex h-9 w-9 items-center justify-center rounded-xl ring-1"
          :class="step.color"
        >
          <Icon :name="step.icon" class="size-5" />
        </div>
        <p class="text-sm font-semibold text-theme-900 dark:text-zinc-100">
          {{ step.title }}
        </p>
        <p
          class="mt-1.5 text-xs leading-relaxed text-theme-700 dark:text-zinc-400"
        >
          {{ step.detail }}
        </p>
      </li>
    </ol>

    <!-- What is NOT in the path -->
    <div class="mt-5 flex justify-center">
      <div
        class="flex max-w-2xl items-start gap-3 rounded-xl border border-dashed border-rose-300 bg-rose-50/70 p-4 dark:border-rose-500/40 dark:bg-rose-500/10"
      >
        <Icon
          name="signal-slash"
          class="size-6 shrink-0 text-rose-500 dark:text-rose-400"
        />
        <p class="text-sm text-rose-700 dark:text-rose-300">
          <strong class="font-semibold"
            >資料不會傳送給 Alt UU 的伺服器。</strong
          >
          Alt UU
          不會攔截你的帳號密碼，也不會保存、轉存或重新發布空大的教材；教材的內容也不會儲存在
          Alt UU。
        </p>
      </div>
    </div>

    <!-- Transparency -->
    <div class="mt-4 flex justify-center">
      <div
        class="flex max-w-2xl items-start gap-3 rounded-xl border border-emerald-300 bg-emerald-50/70 p-4 dark:border-emerald-500/40 dark:bg-emerald-500/10"
      >
        <Icon
          name="code-bracket"
          class="size-6 shrink-0 text-emerald-600 dark:text-emerald-400"
        />
        <p class="text-sm text-emerald-800 dark:text-emerald-300">
          <strong class="font-semibold">經得起檢驗的公開透明。</strong>
          Alt UU 的原始碼以 AGPL-3.0-or-later 授權公開在 GitHub
          上。任何人都能自行檢視、審閱， 確認 App
          實際的運作方式。若你有程式能力，也可以自行編譯、安裝，或修改後使用。
        </p>
      </div>
    </div>

    <!-- Third-party disclaimer -->
    <div class="mt-4 flex justify-center">
      <div
        class="flex max-w-2xl items-start gap-3 rounded-xl border border-theme-200 bg-white/70 p-4 dark:border-zinc-700 dark:bg-zinc-900/50"
      >
        <Icon
          name="shield-check"
          class="size-6 shrink-0 text-theme-700 dark:text-zinc-400"
        />
        <p class="text-sm text-theme-700 dark:text-zinc-300">
          <strong class="font-semibold"
            >Alt UU 是學生自主開發的第三方 App</strong
          >，並非空大官方出品， 與空大平台無任何隸屬或合作關係。Alt UU 在 App
          Store 與 Google Play 上架前，已經過 Apple 與 Google
          的嚴格審核流程，確保 App 不含惡意行為。
        </p>
      </div>
    </div>
  </div>
</template>
