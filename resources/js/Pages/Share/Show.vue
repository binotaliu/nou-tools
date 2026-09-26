<script setup>
// Static copy plus the shared 分享 button; `url` is the site's home URL
// (ShareController), so the shared link is canonical rather than location.href.
// `message` is the blurb that travels with the link, so someone who receives it
// knows what the site is before opening it. It is shown as a preview here.
import { Head } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import ShareButton from '../../Components/ShareButton.vue'

defineProps({
  url: {
    type: String,
    required: true,
  },
})

const message =
  'NOU 小幫手是給空大同學的非官方小工具，由學生自製、完全免費、不需安裝：建立自己的課表並收到上課提醒、檢視今日視訊面授與學校公告、瀏覽開課表與優惠店家，更有線上自習室陪你一起讀書。'

const features = [
  { icon: 'calendar-days', label: '專屬課表', hint: '上課提醒、列印成 PDF' },
  { icon: 'video-camera', label: '今日視訊面授', hint: '所有正在進行的課程' },
  { icon: 'megaphone', label: '學校公告', hint: '即時了解學校最新消息' },
  {
    icon: 'building-storefront',
    label: '優惠店家',
    hint: '由空大同學整理的好康',
  },
  { icon: 'academic-cap', label: '自習室', hint: '和同學一起專注' },
  { icon: 'newspaper', label: '雙週報', hint: '浣熊的空大雙週報' },
]
</script>

<template>
  <Head title="分享 NOU 小幫手 - NOU 小幫手" />

  <AppLayout>
    <div class="mx-auto max-w-3xl space-y-8">
      <div class="flex items-center gap-4">
        <div class="shrink-0 rounded-xl bg-theme-100 p-3 dark:bg-zinc-800">
          <Icon name="share" class="size-8 text-theme-700 dark:text-zinc-300" />
        </div>
        <div>
          <h2
            class="text-3xl font-bold tracking-tight text-theme-700 dark:text-zinc-200"
            data-testid="share-title"
          >
            分享 NOU 小幫手
          </h2>
          <p
            class="mt-1 text-theme-700 dark:text-zinc-400"
            data-testid="share-subtitle"
          >
            喜歡 NOU 小幫手嗎？歡迎分享給其他同學！
          </p>
        </div>
      </div>

      <section
        class="rounded-xl border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        data-testid="share-card"
      >
        <h3 class="text-lg font-semibold text-theme-800 dark:text-zinc-100">
          向同學分享 NOU 小幫手
        </h3>

        <blockquote
          class="mt-4 rounded-lg border border-theme-300 bg-theme-50 p-4 text-sm leading-relaxed text-theme-800 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200"
        >
          <p data-testid="share-message">{{ message }}</p>
          <p class="mt-2 font-mono break-all text-theme-700 dark:text-zinc-400">
            {{ url }}
          </p>
        </blockquote>

        <div class="mt-5 flex flex-wrap items-center gap-3">
          <ShareButton
            title="NOU 小幫手"
            :text="message"
            :url="url"
            dialog-title="分享 NOU 小幫手"
            input-label="NOU 小幫手網址"
            test-id-prefix="share"
          />
          <span class="text-sm text-theme-700 dark:text-zinc-400">
            用 LINE、Discord 或任何你常用的 App 傳給同學吧
          </span>
        </div>
      </section>

      <section aria-labelledby="share-features-heading">
        <h3
          id="share-features-heading"
          class="text-lg font-semibold text-theme-800 dark:text-zinc-100"
        >
          可以跟朋友這樣介紹
        </h3>
        <ul class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <li
            v-for="feature in features"
            :key="feature.label"
            class="flex items-start gap-3 rounded-xl border border-theme-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
            data-testid="share-feature"
          >
            <Icon
              :name="feature.icon"
              class="mt-0.5 size-6 shrink-0 text-theme-700 dark:text-zinc-300"
            />
            <div>
              <p class="font-semibold text-theme-800 dark:text-zinc-100">
                {{ feature.label }}
              </p>
              <p class="text-sm text-theme-700 dark:text-zinc-400">
                {{ feature.hint }}
              </p>
            </div>
          </li>
        </ul>
      </section>

      <p class="text-center text-sm text-theme-700 dark:text-zinc-400">
        謝謝你幫忙推廣，讓更多同學加入 🦝
      </p>
    </div>
  </AppLayout>
</template>
