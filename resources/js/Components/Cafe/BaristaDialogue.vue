<script setup>
// The game-style dialogue box under the room: the squirrel itself stands
// behind the counter on the floor map, so this is its speech panel — a name
// tag, the typed line and the reply buttons. Clicking the text skips the
// typewriter.
defineProps({
  barista: { type: Object, required: true },
})

defineEmits(['more'])
</script>

<template>
  <section
    class="relative rounded-xl border-2 border-b-4 border-theme-300 bg-white p-3 pt-4 dark:border-zinc-600 dark:bg-zinc-800"
    aria-labelledby="cafe-barista-heading"
    data-testid="cafe-barista"
  >
    <!-- the tail of the speech bubble points up at the counter -->
    <span
      class="absolute -top-2 left-[calc(100%-5.5rem)] size-3.5 rotate-45 border-t-2 border-l-2 border-theme-300 bg-white dark:border-zinc-600 dark:bg-zinc-800"
      aria-hidden="true"
    ></span>

    <h2
      id="cafe-barista-heading"
      class="absolute -top-3 left-3 rounded-full border-2 border-theme-300 bg-theme-50 px-2 text-xs font-semibold tracking-widest text-theme-800 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200"
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
  </section>
</template>
