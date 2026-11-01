<script setup>
// 松鼠店長. A chipmunk-cheeked squirrel in a green barista apron, holding a
// latte: flat shapes, each outlined in a darker shade of its own colour. The
// face (and the head's tilt) follows the line's mood. Decorative: the
// dialogue box carries the words. Size it from the caller (`class="size-24"`).
import { computed } from 'vue'

const props = defineProps({
  mood: { type: String, default: 'happy' },
})

const headTilt = computed(
  () =>
    ({ question: 'rotate(-7 42 62)', thinking: 'rotate(5 42 62)' })[props.mood]
)

const fur = '#a8692e'
const furLine = '#74421a'
const cream = '#f6d9a6'
const creamLine = '#d7aa6c'
const ink = '#2a1a10'
</script>

<template>
  <svg
    viewBox="0 0 96 96"
    class="shrink-0"
    fill="none"
    aria-hidden="true"
    focusable="false"
    stroke-linecap="round"
    stroke-linejoin="round"
    data-testid="cafe-barista-portrait"
  >
    <!-- tail: one fat plume behind the back, rolled over at the top -->
    <path
      d="M58 96C76 96 95 84 95 60C95 36 88 17 74 16C62 15 56 24 60 32C63 37 70 37 72 32C74 40 72 52 68 62C64 72 60 78 56 82Z"
      :fill="fur"
      :stroke="furLine"
      stroke-width="1.6"
    />
    <path
      d="M63 88C80 84 88.5 71 88.5 56C88.5 40 84 26 77.5 26C72 26 70 33 73.5 37C76.5 40 81 37 80 33"
      stroke="#8a5222"
      stroke-width="3"
    />

    <!-- body; the arms' outline goes under it so only their outer edge shows -->
    <path
      d="M25 84Q26 76 34 72.5M59 84Q58 76 50 72.5"
      :stroke="furLine"
      stroke-width="7.6"
    />
    <path
      d="M22 97C22 80 30 66 42 66C54 66 62 80 62 97Z"
      :fill="fur"
      :stroke="furLine"
      stroke-width="1.6"
    />

    <!-- apron -->
    <path
      d="M31 96V79Q31 74.5 35.5 74.5H48.5Q53 74.5 53 79V96Z"
      fill="#5b8a57"
      stroke="#3e6340"
      stroke-width="1.4"
    />
    <rect
      x="37"
      y="85"
      width="10"
      height="7"
      rx="1.5"
      stroke="#3e6340"
      stroke-width="1.2"
    />

    <!-- arms, and a latte held in both paws -->
    <path
      d="M25 84Q26 76 34 72.5M59 84Q58 76 50 72.5"
      :stroke="fur"
      stroke-width="4.4"
    />
    <path
      d="M37 67.5H47L46.2 75.6Q46 77 44.6 77H39.4Q38 77 37.8 75.6Z"
      fill="#fbf4e8"
      stroke="#cbb89c"
      stroke-width="1.2"
    />
    <ellipse cx="42" cy="67.5" rx="5" ry="1.4" fill="#7a4a28" />
    <circle
      cx="36.8"
      cy="73"
      r="2.7"
      :fill="fur"
      :stroke="furLine"
      stroke-width="1.2"
    />
    <circle
      cx="47.2"
      cy="73"
      r="2.7"
      :fill="fur"
      :stroke="furLine"
      stroke-width="1.2"
    />

    <g :transform="headTilt">
      <!-- ears -->
      <path
        d="M19 31Q17 16 23 8Q27 5.5 30 10Q35 16 36 24Z"
        :fill="fur"
        :stroke="furLine"
        stroke-width="1.6"
      />
      <path d="M22 27Q21.5 18 25 12.5Q29.5 16 32 23Z" fill="#d9a46c" />
      <path
        d="M65 31Q67 16 61 8Q57 5.5 54 10Q49 16 48 24Z"
        :fill="fur"
        :stroke="furLine"
        stroke-width="1.6"
      />
      <path d="M62 27Q62.5 18 59 12.5Q54.5 16 52 23Z" fill="#d9a46c" />

      <!-- face: big round cheeks, outlined as one shape (stroke pass, then fill) -->
      <g :stroke="creamLine" stroke-width="3.2">
        <circle cx="25" cy="50" r="14" />
        <circle cx="59" cy="50" r="14" />
        <ellipse cx="42" cy="51" rx="15" ry="13" />
        <ellipse cx="42" cy="42" rx="22" ry="12" />
      </g>
      <g :fill="cream">
        <circle cx="25" cy="50" r="14" />
        <circle cx="59" cy="50" r="14" />
        <ellipse cx="42" cy="51" rx="15" ry="13" />
        <ellipse cx="42" cy="42" rx="22" ry="12" />
      </g>

      <!-- crown of the head, coming down to a point at the nose -->
      <path
        d="M42 12C56 12 66.5 21 67 35C63 33 58 30.5 54 32.5C49 35 45 41 42 47C39 41 35 35 30 32.5C26 30.5 21 33 17 35C17.5 21 28 12 42 12Z"
        :fill="fur"
        :stroke="furLine"
        stroke-width="1.6"
      />
      <path
        d="M37.5 13.5L39 7.5L41.5 12.5L44.5 6.5L46 13"
        :fill="fur"
        :stroke="furLine"
        stroke-width="1.6"
      />

      <!-- blush -->
      <ellipse cx="22" cy="51" rx="5.5" ry="3.6" fill="#f3a29b" opacity="0.6" />
      <ellipse cx="62" cy="51" rx="5.5" ry="3.6" fill="#f3a29b" opacity="0.6" />

      <!-- eyes -->
      <path
        v-if="mood === 'happy'"
        d="M29.6 42.4Q32 38.8 34.4 42.4M49.6 42.4Q52 38.8 54.4 42.4"
        :stroke="ink"
        stroke-width="1.9"
      />
      <g
        v-else
        :fill="ink"
        :transform="mood === 'thinking' ? 'translate(1 -1.2)' : undefined"
      >
        <ellipse
          cx="32"
          cy="41.5"
          :rx="mood === 'surprised' ? 2.8 : 2.2"
          :ry="mood === 'surprised' ? 3 : 2.7"
        />
        <ellipse
          cx="52"
          cy="41.5"
          :rx="mood === 'surprised' ? 2.8 : 2.2"
          :ry="mood === 'surprised' ? 3 : 2.7"
        />
      </g>

      <!-- brows -->
      <g :stroke="furLine" stroke-width="1.6">
        <path
          v-if="mood === 'worried'"
          d="M28.5 35.5L34.5 33.8M55.5 35.5L49.5 33.8"
        />
        <path
          v-else-if="mood === 'question'"
          d="M29 35.2Q32 34.4 35 35.2M49 33.4Q52 31.4 55.5 33"
        />
        <path
          v-else-if="mood === 'thinking'"
          d="M29 34.6H35M49.5 33.8Q52.5 32.6 55.5 33.8"
        />
        <path
          v-else-if="mood === 'surprised'"
          d="M28.5 34Q32 32 35.5 34M48.5 34Q52 32 55.5 34"
        />
      </g>

      <!-- nose -->
      <path
        d="M39 46.2Q42 45 45 46.2Q44.4 48.8 42 49.6Q39.6 48.8 39 46.2Z"
        fill="#6b3a1e"
      />

      <!-- mouth -->
      <g stroke="#d4566a" stroke-width="1.6">
        <path
          v-if="mood === 'happy'"
          d="M37.4 52.4Q39.7 55.4 42 52.8Q44.3 55.4 46.6 52.4Q45.5 58 42 58Q38.5 58 37.4 52.4Z"
          fill="#e56f7d"
        />
        <ellipse
          v-else-if="mood === 'surprised'"
          cx="42"
          cy="54.6"
          rx="2"
          ry="2.4"
          fill="#e56f7d"
        />
        <path
          v-else-if="mood === 'worried'"
          d="M37.6 55Q39.8 52.4 42 54Q44.2 52.4 46.4 55"
        />
        <path
          v-else-if="mood === 'thinking'"
          d="M38.5 54.4Q42 53.4 45.6 54.8"
        />
        <path v-else d="M37.4 52.4Q39.7 55.4 42 52.8Q44.3 55.4 46.6 52.4" />
      </g>
      <path
        v-if="mood === 'worried'"
        d="M67 32C65.3 35.2 65.3 37.4 67 38.2C68.7 37.4 68.7 35.2 67 32Z"
        fill="#9fd3f0"
      />
    </g>
  </svg>
</template>
