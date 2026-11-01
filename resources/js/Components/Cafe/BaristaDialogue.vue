<script setup>
// The game-style dialogue box under the counter: portrait on the left, the
// typed line in a speech panel, reply buttons below. Clicking the text skips
// the typewriter.
import Barista from './Barista.vue'

defineProps({
  barista: { type: Object, required: true },
})

defineEmits(['more'])
</script>

<template>
  <section
    class="flex items-start gap-3 rounded-xl border-2 border-b-4 border-theme-300 bg-white p-3 dark:border-zinc-600 dark:bg-zinc-800"
    aria-labelledby="cafe-barista-heading"
    data-testid="cafe-barista"
  >
    <Barista :mood="barista.mood" />

    <div class="min-w-0 flex-1">
      <h2
        id="cafe-barista-heading"
        class="text-xs font-semibold tracking-widest text-theme-700 dark:text-zinc-400"
      >
        松鼠店長
      </h2>

      <p
        v-if="barista.failed"
        class="mt-1 text-sm text-theme-800 dark:text-zinc-200"
        data-testid="cafe-barista-quiet"
      >
        店長在後面整理豆子，晚點再來找他聊聊吧。
      </p>

      <template v-else>
        <!-- The full text for assistive tech; the typed copy is visual only. -->
        <p class="sr-only" role="status" data-testid="cafe-barista-line">
          {{ barista.fullText }}
        </p>
        <button
          type="button"
          class="mt-1 block min-h-12 w-full cursor-default text-left text-sm leading-relaxed text-theme-900 dark:text-zinc-100"
          aria-hidden="true"
          tabindex="-1"
          @click="barista.skipTyping()"
        >
          {{ barista.typed
          }}<span
            v-if="barista.typing"
            class="ml-0.5 inline-block h-4 w-1 translate-y-0.5 blink-colon bg-theme-700 dark:bg-zinc-300"
          ></span>
        </button>

        <div class="mt-2 flex flex-wrap gap-2">
          <button
            v-for="reply in barista.replies"
            :key="reply.label"
            type="button"
            class="rounded-lg border-2 border-b-4 border-theme-300 bg-theme-50 px-3 py-1.5 text-sm font-medium text-theme-900 transition hover:border-theme-400 active:translate-y-px active:border-b-2 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
            data-testid="cafe-barista-reply"
            @click="barista.choose(reply)"
          >
            {{ reply.label }}
          </button>
          <button
            v-if="!barista.replies.length && !barista.loading"
            type="button"
            class="rounded-lg border-2 border-b-4 border-theme-300 bg-white px-3 py-1.5 text-sm font-medium text-theme-900 transition hover:border-theme-400 active:translate-y-px active:border-b-2 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
            accesskey="4"
            data-testid="cafe-barista-more"
            @click="$emit('more')"
          >
            再聊一句
          </button>
        </div>
      </template>
    </div>
  </section>
</template>
