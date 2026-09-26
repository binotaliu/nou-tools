<script setup>
// Homepage hero: three slides promoting 課表管理, 學習進度表 and 自習室. Only
// shown to visitors without a remembered schedule (see Home/Index.vue).
// Slides share one grid cell and cross-fade, so the hero is always as tall as
// its tallest slide and nothing jumps when it advances. Slide state lives in
// useHeroCarousel.js; the illustrations are decorative, so the slide copy
// carries all the meaning.
import { Link } from '@inertiajs/vue3'
import Icon from '../Icon.vue'
import ProgressIllustration from './ProgressIllustration.vue'
import ScheduleIllustration from './ScheduleIllustration.vue'
import StudyRoomIllustration from './StudyRoomIllustration.vue'
import useHeroCarousel from '../../Composables/useHeroCarousel'

const tagline = '為空大同學製作的非官方小工具'

const slides = [
  {
    key: 'schedule',
    title: '課表管理',
    description: '方便檢視下次面授時間',
    cta: '建立課表以開始使用',
    href: '/schedules/my',
    feature: 'schedule',
    illustration: ScheduleIllustration,
  },
  {
    key: 'learning-progress',
    title: '學習進度表',
    description: '掌握自己的進度',
    cta: '建立課表以開始使用',
    href: '/schedules/my',
    feature: 'learning_progress',
    illustration: ProgressIllustration,
  },
  {
    key: 'study-room',
    title: '自習室',
    description: '與同學在雲端一起用功',
    cta: '建立課表以開始使用',
    href: '/schedules/my',
    feature: 'study_room',
    illustration: StudyRoomIllustration,
  },
]

const {
  index,
  playing,
  goTo,
  next,
  previous,
  togglePaused,
  hold,
  release,
  onTouchStart,
  onTouchEnd,
} = useHeroCarousel({ count: 3 })
</script>

<template>
  <section
    data-testid="home-hero"
    aria-roledescription="carousel"
    aria-label="功能介紹"
    class="overflow-hidden rounded-lg border border-theme-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 print:hidden"
    @mouseenter="hold"
    @mouseleave="release"
    @focusin="hold"
    @focusout="release"
    @touchstart.passive="onTouchStart"
    @touchend.passive="onTouchEnd"
  >
    <div class="grid" aria-live="off">
      <div
        v-for="(slide, i) in slides"
        :key="slide.key"
        :data-testid="`hero-slide-${slide.key}`"
        role="group"
        aria-roledescription="slide"
        :aria-label="`${i + 1} / ${slides.length}`"
        :inert="i !== index"
        :aria-hidden="i !== index"
        class="col-start-1 row-start-1 flex flex-col-reverse items-center gap-6 p-6 transition-opacity duration-500 motion-reduce:transition-none md:flex-row md:gap-10 md:px-10 md:py-8"
        :class="i === index ? 'opacity-100' : 'pointer-events-none opacity-0'"
      >
        <div class="flex-1 text-center md:text-left">
          <p
            class="inline-block rounded-full border border-theme-200 bg-theme-50 px-3 py-1 text-xs font-semibold text-theme-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300"
          >
            {{ tagline }}
          </p>
          <h2
            class="mt-4 text-3xl font-bold text-theme-900 md:text-4xl dark:text-zinc-100"
          >
            {{ slide.title }}
          </h2>
          <p class="mt-2 text-lg text-theme-700 dark:text-zinc-300">
            {{ slide.description }}
          </p>
          <Link
            :href="slide.href"
            data-analytics-event="home_hero_click"
            :data-analytics-feature="slide.feature"
            class="mt-5 inline-flex items-center justify-center gap-2 rounded-lg border border-theme-700 bg-theme-700 px-5 py-2 font-semibold text-white transition hover:bg-theme-800"
          >
            {{ slide.cta }}
            <Icon name="chevron-right" class="size-4" />
          </Link>
        </div>

        <component
          :is="slide.illustration"
          class="w-full max-w-64 shrink-0 md:max-w-80"
        />
      </div>
    </div>

    <div
      class="flex items-center justify-center gap-3 px-6 pb-4 text-theme-700 dark:text-zinc-300"
    >
      <button
        type="button"
        data-testid="hero-previous"
        aria-label="上一張"
        class="rounded-full p-1.5 transition hover:bg-theme-100 dark:hover:bg-zinc-800 pointer-coarse:p-3"
        @click="previous"
      >
        <Icon name="chevron-left" class="size-5" />
      </button>

      <div class="flex items-center gap-1">
        <button
          v-for="(slide, i) in slides"
          :key="slide.key"
          type="button"
          :data-testid="`hero-dot-${slide.key}`"
          :aria-label="`前往：${slide.title}`"
          :aria-current="i === index"
          class="group flex size-6 items-center justify-center pointer-coarse:size-11"
          @click="goTo(i)"
        >
          <span
            class="h-2 rounded-full transition-all"
            :class="
              i === index
                ? 'w-6 bg-theme-600 dark:bg-theme-500'
                : 'w-2 bg-theme-200 group-hover:bg-theme-300 dark:bg-zinc-600 dark:group-hover:bg-zinc-500'
            "
          />
        </button>
      </div>

      <button
        type="button"
        data-testid="hero-next"
        aria-label="下一張"
        class="rounded-full p-1.5 transition hover:bg-theme-100 dark:hover:bg-zinc-800 pointer-coarse:p-3"
        @click="next"
      >
        <Icon name="chevron-right" class="size-5" />
      </button>

      <button
        type="button"
        data-testid="hero-toggle-autoplay"
        :aria-label="playing ? '暫停自動播放' : '開始自動播放'"
        class="ml-2 rounded-full p-1.5 transition hover:bg-theme-100 dark:hover:bg-zinc-800 pointer-coarse:p-3"
        @click="togglePaused"
      >
        <Icon :name="playing ? 'pause' : 'play'" class="size-5" />
      </button>
    </div>
  </section>
</template>
