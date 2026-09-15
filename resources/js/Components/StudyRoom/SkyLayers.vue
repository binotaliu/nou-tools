<script setup>
// Vue port of resources/views/study-room/partials/_sky-layers.blade.php: the
// sky itself (gradient, WebGL shader canvas, stars, sun/moon, clouds).
// `skyLayout` is 'garden' (the strip above the ground floor) or 'focus'
// (the fullscreen focus mode) — both read the same `sky` composable state,
// just drawn at different sizes.
import { computed, onMounted, onUnmounted, ref } from 'vue'

const props = defineProps({
  sky: { type: Object, required: true },
  skyLayout: { type: String, required: true },
})

const isFocus = computed(() => props.skyLayout === 'focus')
const testPrefix = computed(() =>
  isFocus.value ? 'study-room-focus-' : 'study-room-'
)
const canvasEl = ref(null)

onMounted(() => {
  if (canvasEl.value) {
    props.sky.mountSkyCanvas(canvasEl.value, props.skyLayout)
  }
})

onUnmounted(() => {
  props.sky.unmountSkyCanvas(props.skyLayout)
})
</script>

<template>
  <div class="absolute inset-0" :style="sky.gardenSkyStyle()"></div>

  <canvas
    ref="canvasEl"
    class="absolute inset-0 size-full transition-opacity duration-700"
    :style="sky.skyCanvasStyle(skyLayout)"
    :data-testid="testPrefix + 'sky-canvas'"
    aria-hidden="true"
  ></canvas>

  <div
    class="absolute inset-0 transition-opacity duration-1000"
    :style="sky.starsStyle()"
    :data-testid="testPrefix + 'stars'"
    aria-hidden="true"
  >
    <span
      v-for="star in sky.starsFor(skyLayout)"
      :key="star.id"
      class="absolute animate-twinkle rounded-full bg-white"
      :style="sky.starStyle(star, skyLayout)"
    ></span>
  </div>

  <div
    v-show="!sky.isSkyCanvasActive(skyLayout)"
    class="absolute inset-0 transition-opacity duration-1000"
    :style="sky.sunGlowStyle(skyLayout)"
    aria-hidden="true"
  ></div>

  <span
    v-show="sky.sky.moonVisible"
    class="absolute -translate-x-1/2 -translate-y-1/2 overflow-hidden rounded-full bg-[radial-gradient(circle_at_38%_35%,#fffdf0_0%,#f3edd0_60%,#d9d2ae_100%)] transition-[top,left] duration-1000"
    :class="
      isFocus
        ? 'size-16 shadow-[0_0_48px_18px_rgba(255,250,220,0.3)] sm:size-20'
        : 'size-5 shadow-[0_0_16px_6px_rgba(255,250,220,0.3)]'
    "
    :style="sky.moonStyle(skyLayout)"
    :data-testid="testPrefix + 'moon'"
    aria-hidden="true"
  >
    <span
      class="absolute top-[30%] left-[55%] rounded-full bg-black/10"
      :class="isFocus ? 'size-[18%]' : 'size-1.5'"
    ></span>
    <span
      class="absolute top-[58%] left-[28%] rounded-full bg-black/10"
      :class="isFocus ? 'size-[12%]' : 'size-1'"
    ></span>
    <span
      class="absolute inset-0 rounded-full"
      :style="sky.moonShadowStyle()"
    ></span>
  </span>

  <span
    v-show="sky.sky.sunVisible"
    class="absolute -translate-x-1/2 -translate-y-1/2 rounded-full bg-[#ffe488] transition-[top,left] duration-1000"
    :class="
      isFocus
        ? 'size-20 shadow-[0_0_90px_40px_rgba(255,214,90,0.45)] sm:size-28'
        : 'size-7 shadow-[0_0_28px_12px_rgba(255,214,90,0.45)]'
    "
    :style="sky.sunStyle(skyLayout)"
    :data-testid="testPrefix + 'sun'"
    aria-hidden="true"
  ></span>

  <div
    class="absolute inset-x-0 top-0 h-[55%] opacity-(--g-cloud-opacity) transition-opacity duration-1000"
    aria-hidden="true"
  >
    <div
      class="absolute top-[18%] left-[12%] h-3 w-16 animate-drift [animation-duration:500s]"
      :class="isFocus ? 'scale-[2.5] sm:scale-[3.5]' : ''"
    >
      <span
        class="absolute right-0 bottom-0 left-0 h-2.5 rounded-full bg-(--g-cloud)"
      ></span>
      <span
        class="absolute bottom-0.5 left-3 size-4 rounded-full bg-(--g-cloud)"
      ></span>
      <span
        class="absolute bottom-0.5 left-7 size-3 rounded-full bg-(--g-cloud)"
      ></span>
    </div>
    <div
      class="absolute top-[42%] left-[58%] h-2.5 w-12 animate-drift [animation-delay:-300s] [animation-duration:700s]"
      :class="isFocus ? 'scale-[2.5] sm:scale-[3.5]' : ''"
    >
      <span
        class="absolute right-0 bottom-0 left-0 h-2 rounded-full bg-(--g-cloud)"
      ></span>
      <span
        class="absolute bottom-0.5 left-2 size-3 rounded-full bg-(--g-cloud)"
      ></span>
      <span
        class="absolute bottom-0.5 left-5 size-2.5 rounded-full bg-(--g-cloud)"
      ></span>
    </div>
    <div
      class="absolute top-[8%] left-[78%] h-3.5 w-20 animate-drift [animation-delay:-467s] [animation-duration:600s]"
      :class="isFocus ? 'scale-[2.5] sm:scale-[3.5]' : ''"
    >
      <span
        class="absolute right-0 bottom-0 left-0 h-3 rounded-full bg-(--g-cloud)"
      ></span>
      <span
        class="absolute bottom-0.5 left-4 size-5 rounded-full bg-(--g-cloud)"
      ></span>
      <span
        class="absolute bottom-0.5 left-10 size-4 rounded-full bg-(--g-cloud)"
      ></span>
    </div>
    <div
      v-if="isFocus"
      class="absolute top-[30%] left-[32%] h-3 w-14 scale-[2.5] animate-drift [animation-delay:-133s] [animation-duration:800s] sm:scale-[3.5]"
    >
      <span
        class="absolute right-0 bottom-0 left-0 h-2.5 rounded-full bg-(--g-cloud)"
      ></span>
      <span
        class="absolute bottom-0.5 left-2 size-3.5 rounded-full bg-(--g-cloud)"
      ></span>
      <span
        class="absolute bottom-0.5 left-6 size-3 rounded-full bg-(--g-cloud)"
      ></span>
    </div>
  </div>
</template>
