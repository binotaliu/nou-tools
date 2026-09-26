<script setup>
// Live camera QR scanner. Emits `detected` once with the decoded text, then
// stops the camera. Uses the browser's BarcodeDetector where it exists and
// falls back to jsQR (lazy-loaded, so it costs nothing until a scan starts).
import { onBeforeUnmount, onMounted, ref } from 'vue'

const emit = defineEmits(['detected', 'close'])

const video = ref(null)
const error = ref('')
// Spoken through a polite live region: starting → scanning → found, or the error.
const status = ref('正在啟動相機…')

let stream = null
let frameRequest = null
let stopped = false

function stop() {
  stopped = true
  if (frameRequest !== null) {
    cancelAnimationFrame(frameRequest)
  }
  stream?.getTracks().forEach(track => track.stop())
  stream = null
}

async function createDecoder() {
  if ('BarcodeDetector' in window) {
    try {
      const detector = new window.BarcodeDetector({ formats: ['qr_code'] })

      return async source => {
        const [code] = await detector.detect(source)

        return code?.rawValue ?? null
      }
    } catch {
      // Constructing can throw when qr_code isn't supported; use jsQR.
    }
  }

  const { default: jsQR } = await import('jsqr')
  const canvas = document.createElement('canvas')
  const context = canvas.getContext('2d', { willReadFrequently: true })

  return async source => {
    canvas.width = source.videoWidth
    canvas.height = source.videoHeight
    context.drawImage(source, 0, 0, canvas.width, canvas.height)
    const image = context.getImageData(0, 0, canvas.width, canvas.height)

    return jsQR(image.data, image.width, image.height)?.data ?? null
  }
}

async function start() {
  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' } },
      audio: false,
    })
  } catch {
    error.value =
      '無法使用相機。請允許此網站使用相機，或關閉相機後改用貼上連結或選取截圖。'
    status.value = ''
    return
  }

  if (stopped) {
    stop()
    return
  }

  video.value.srcObject = stream
  await video.value.play()

  const decode = await createDecoder()

  if (stopped) {
    return
  }

  status.value =
    '相機已啟動，正在掃描 QR Code，請將課表頁面上的 QR Code 對準畫面。'

  const tick = async () => {
    if (stopped) {
      return
    }

    if (video.value.readyState >= 2 && video.value.videoWidth > 0) {
      let text = null

      try {
        text = await decode(video.value)
      } catch {
        // A frame that fails to decode is just a miss; keep scanning.
      }

      if (text) {
        stop()
        status.value = '已讀取到 QR Code。'
        emit('detected', text)
        return
      }
    }

    frameRequest = requestAnimationFrame(tick)
  }

  tick()
}

onMounted(start)
onBeforeUnmount(stop)
</script>

<template>
  <div data-testid="qr-scanner">
    <p
      v-if="error"
      role="alert"
      data-testid="qr-scanner-error"
      class="rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950 dark:text-red-300"
    >
      {{ error }}
    </p>
    <div
      v-else
      class="relative aspect-square w-full overflow-hidden rounded-lg bg-black"
    >
      <video
        ref="video"
        aria-label="相機預覽畫面，用來掃描課表的 QR Code"
        class="size-full object-cover"
        muted
        playsinline
      ></video>
      <div
        class="pointer-events-none absolute inset-8 rounded-lg border-2 border-white/80"
      ></div>
    </div>
    <p
      v-if="!error"
      class="mt-2 text-center text-sm text-theme-700 dark:text-zinc-400"
    >
      將相機對準課表頁面上的 QR Code
    </p>
    <p
      role="status"
      aria-live="polite"
      data-testid="qr-scanner-status"
      class="sr-only"
    >
      {{ status }}
    </p>
    <p
      data-testid="qr-scanner-alternative"
      class="mt-2 text-center text-sm text-theme-700 dark:text-zinc-400"
    >
      不方便使用相機？請按「關閉相機」，改用貼上備份連結，或選取備份截圖。
    </p>
    <div class="mt-3 flex justify-end">
      <button
        type="button"
        data-testid="qr-scanner-close"
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 font-semibold text-theme-900 transition hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
        @click="emit('close')"
      >
        關閉相機
      </button>
    </div>
  </div>
</template>
