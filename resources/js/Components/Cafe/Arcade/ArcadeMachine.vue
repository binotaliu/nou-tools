<script setup>
// The kiosk inside the arcade modal: a dark screen that lists the games and
// shows the chosen one. A new game is one more entry in GAMES (plus its
// component); the shell itself does not change.
import { ref } from 'vue'
import AcornIcon from '../../Acorns/AcornIcon.vue'
import HoroscopeGame from './HoroscopeGame.vue'

defineProps({
  canPlay: { type: Boolean, required: true },
  hasSchedule: { type: Boolean, required: true },
  hasNickname: { type: Boolean, required: true },
  balance: { type: Number, required: true },
})

const emit = defineEmits(['spent'])

const GAMES = [
  {
    key: 'horoscope',
    label: '星座運勢',
    blurb: '投幣抽今天的運勢',
    emoji: '🔮',
    component: HoroscopeGame,
  },
]

const current = ref(null)
</script>

<template>
  <div
    class="rounded-xl border-4 border-[#5e4535] bg-[#241c2e] p-3 text-amber-50"
    data-testid="arcade-machine"
  >
    <template v-if="current === null">
      <div class="flex items-center justify-between gap-2">
        <h2 class="text-base font-semibold text-amber-100">選一個遊戲</h2>
        <span
          class="flex items-center gap-1 text-sm font-semibold text-amber-300 tabular-nums"
          data-testid="arcade-balance"
        >
          <AcornIcon /> {{ balance }}
        </span>
      </div>

      <ul class="mt-2 space-y-2">
        <li v-for="game in GAMES" :key="game.key">
          <button
            type="button"
            class="flex w-full items-center gap-3 rounded-lg border-2 border-white/15 bg-white/5 px-3 py-2 text-left transition hover:border-amber-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-300"
            :data-testid="`arcade-game-${game.key}`"
            @click="current = game"
          >
            <span class="text-2xl" aria-hidden="true">{{ game.emoji }}</span>
            <span>
              <span class="block text-sm font-semibold">{{ game.label }}</span>
              <span class="block text-xs text-amber-100/70">{{
                game.blurb
              }}</span>
            </span>
          </button>
        </li>
      </ul>
    </template>

    <template v-else>
      <button
        type="button"
        class="mb-2 text-sm text-amber-100/80 underline"
        data-testid="arcade-back"
        @click="current = null"
      >
        返回選單
      </button>

      <component
        :is="current.component"
        :can-play="canPlay"
        :has-schedule="hasSchedule"
        :has-nickname="hasNickname"
        :balance="balance"
        @spent="emit('spent', $event)"
      />
    </template>
  </div>
</template>
