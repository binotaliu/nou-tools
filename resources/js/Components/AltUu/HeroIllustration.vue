<script setup>
// Decorative HTML drawing of three Alt UU screens (course list, material
// directory, player), coloured with the site's theme tokens so it follows the
// reader's theme and dark mode. The phones sit on a fixed-width stage that the
// column clips symmetrically, so narrow screens lose the outer edges of the
// side phones rather than shrinking the front one. The card's bottom edge
// crops all three, as in an app store listing.
//
// The front phone rotates on a timer (paused on hover and under reduced
// motion), and clicking a side phone brings it to the front.
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { prefersReducedMotion } from '../../Composables/useReduceMotion'
import PhoneFrame from './PhoneFrame.vue'
import CourseListScreen from './CourseListScreen.vue'
import MaterialsScreen from './MaterialsScreen.vue'
import PlayerScreen from './PlayerScreen.vue'

const ROTATE_EVERY_MS = 10000

const screens = [
  { key: 'courses', component: CourseListScreen },
  { key: 'materials', component: MaterialsScreen },
  { key: 'player', component: PlayerScreen },
]

// Where a phone sits, by its distance from the front one along the rotation.
const positions = [
  {
    name: 'front',
    classes: 'top-0 left-36 z-10 scale-[0.85]',
  },
  {
    name: 'right',
    classes: 'top-10 left-72 z-0 scale-75 cursor-pointer',
  },
  {
    name: 'left',
    classes: 'top-10 left-0 z-0 scale-75 cursor-pointer',
  },
]

const frontIndex = ref(0)
const paused = ref(false)
let timer = null

const positionOf = index =>
  positions[(index - frontIndex.value + screens.length) % screens.length]

const rotate = () => {
  if (!paused.value) {
    frontIndex.value = (frontIndex.value + 1) % screens.length
  }
}

const bringToFront = index => {
  frontIndex.value = index
}

onMounted(() => {
  if (prefersReducedMotion.value) {
    return
  }

  timer = setInterval(rotate, ROTATE_EVERY_MS)
})

onBeforeUnmount(() => {
  clearInterval(timer)
})
</script>

<template>
  <div
    class="flex h-72 justify-center overflow-hidden select-none"
    aria-hidden="true"
    data-testid="alt-uu-hero-illustration"
    @mouseenter="paused = true"
    @mouseleave="paused = false"
  >
    <div class="relative h-full w-136 shrink-0">
      <div
        v-for="(screen, index) in screens"
        :key="screen.key"
        class="absolute origin-top transition-all duration-700 ease-in-out motion-reduce:transition-none"
        :class="positionOf(index).classes"
        :data-testid="`alt-uu-hero-phone-${screen.key}`"
        :data-position="positionOf(index).name"
        @click="bringToFront(index)"
      >
        <PhoneFrame><component :is="screen.component" /></PhoneFrame>
      </div>
    </div>
  </div>
</template>
