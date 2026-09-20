<script setup>
// A form date field that looks and behaves the same in every browser. Native
// `<input type="date">` renders dd/mm/yyyy or mm/dd/yyyy depending on the
// browser's locale, and Safari shows today's date for an empty value. This
// shows a fixed `M/D` label (or a placeholder when unset) and submits the
// value as `YYYY-MM-DD` through a hidden input, so it drops into a native form
// POST. The calendar is teleported to <body> because the field usually sits
// inside an `overflow-x-auto` table that would clip an absolutely positioned
// popover.
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'

// `id`, `class` and attributes like `data-offline-disable` belong on the
// trigger button (so a <label for> works), not the wrapper.
defineOptions({ inheritAttrs: false })

const props = defineProps({
  // Omit for a field that isn't part of a native form submission.
  name: { type: String, default: null },
  modelValue: { type: String, default: null },
  label: { type: String, required: true },
  // ISO dates (`YYYY-MM-DD` or a longer ISO string, the date part is used).
  // `today` comes from the server so it follows Taipei time, not the
  // browser's; `initialMonth` is where an empty field's calendar opens.
  today: { type: String, default: null },
  initialMonth: { type: String, default: null },
  placeholder: { type: String, default: '未設定' },
  // `cell` fills a table cell and shows `M/D`; `field` is a bordered
  // standalone control that shows the full date including the year.
  variant: { type: String, default: 'cell' },
  clearable: { type: Boolean, default: true },
})

const emit = defineEmits(['change'])

// Monday-first: index 0 is Monday, matching `weekdayOf`.
const WEEKDAYS = ['一', '二', '三', '四', '五', '六', '日']
const POPOVER_WIDTH = 272
const POPOVER_GAP = 4

function parseIso(iso) {
  const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(iso ?? '')

  if (!match) {
    return null
  }

  const [year, month, day] = match.slice(1).map(Number)
  const check = new Date(Date.UTC(year, month - 1, day))

  if (check.getUTCMonth() !== month - 1) {
    return null
  }

  return { year, month, day }
}

function toIso({ year, month, day }) {
  return [
    String(year).padStart(4, '0'),
    String(month).padStart(2, '0'),
    String(day).padStart(2, '0'),
  ].join('-')
}

function addDays({ year, month, day }, offset) {
  const date = new Date(Date.UTC(year, month - 1, day + offset))

  return {
    year: date.getUTCFullYear(),
    month: date.getUTCMonth() + 1,
    day: date.getUTCDate(),
  }
}

function weekdayOf({ year, month, day }) {
  return (new Date(Date.UTC(year, month - 1, day)).getUTCDay() + 6) % 7
}

function daysInMonth(year, month) {
  return new Date(Date.UTC(year, month, 0)).getUTCDate()
}

function describe(date) {
  return `${date.year} 年 ${date.month} 月 ${date.day} 日（${WEEKDAYS[weekdayOf(date)]}）`
}

const value = ref(props.modelValue ?? '')

watch(
  () => props.modelValue,
  next => {
    value.value = next ?? ''
  }
)

const selected = computed(() => parseIso(value.value))
const todayDate = computed(() => parseIso(props.today))

const buttonText = computed(() => {
  if (!selected.value) {
    return props.placeholder
  }

  const { year, month, day } = selected.value

  return props.variant === 'field'
    ? `${year}/${month}/${day}（${WEEKDAYS[weekdayOf(selected.value)]}）`
    : `${month}/${day}`
})

const buttonTitle = computed(() =>
  selected.value ? describe(selected.value) : props.placeholder
)

// --- popover state ---
const open = ref(false)
const trigger = ref(null)
const popover = ref(null)
const popoverStyle = ref({})
const viewYear = ref(2000)
const viewMonth = ref(1)
const focusedIso = ref('')

const monthLabel = computed(() => `${viewYear.value} 年 ${viewMonth.value} 月`)

const cells = computed(() => {
  const leading = weekdayOf({
    year: viewYear.value,
    month: viewMonth.value,
    day: 1,
  })
  const total = daysInMonth(viewYear.value, viewMonth.value)
  const days = Array.from({ length: total }, (_, index) => {
    const date = {
      year: viewYear.value,
      month: viewMonth.value,
      day: index + 1,
    }

    return { date, iso: toIso(date) }
  })

  return [...Array.from({ length: leading }, () => null), ...days]
})

function showMonth(date) {
  viewYear.value = date.year
  viewMonth.value = date.month
}

function shiftMonth(offset) {
  const index = viewYear.value * 12 + (viewMonth.value - 1) + offset

  viewYear.value = Math.floor(index / 12)
  viewMonth.value = (index % 12) + 1
  focusedIso.value = ''
}

function placePopover() {
  const rect = trigger.value?.getBoundingClientRect()

  if (!rect) {
    return
  }

  const popoverHeight = popover.value?.offsetHeight ?? 320
  const left = Math.max(
    8,
    Math.min(rect.left, window.innerWidth - POPOVER_WIDTH - 8)
  )
  const fitsBelow =
    rect.bottom + POPOVER_GAP + popoverHeight <= window.innerHeight
  const top = fitsBelow
    ? rect.bottom + POPOVER_GAP
    : Math.max(8, rect.top - POPOVER_GAP - popoverHeight)

  popoverStyle.value = {
    top: `${top}px`,
    left: `${left}px`,
    width: `${POPOVER_WIDTH}px`,
  }
}

function focusDay() {
  popover.value
    ?.querySelector('[data-day][tabindex="0"]')
    ?.focus({ preventScroll: true })
}

async function openPopover() {
  const start =
    selected.value ?? todayDate.value ?? parseIso(props.initialMonth)

  if (start) {
    showMonth(start)
  }

  focusedIso.value = selected.value ? value.value : ''
  open.value = true
  placePopover()
  await nextTick()
  // The first measurement guessed at the height; now it's rendered.
  placePopover()
  focusDay()
}

function closePopover({ restoreFocus = false } = {}) {
  open.value = false

  if (restoreFocus) {
    trigger.value?.focus()
  }
}

function setValue(next) {
  value.value = next
  emit('change', next)
}

function pick(date) {
  setValue(toIso(date))
  closePopover({ restoreFocus: true })
}

function clear() {
  setValue('')
  closePopover({ restoreFocus: true })
}

function pickToday() {
  if (todayDate.value) {
    pick(todayDate.value)
  }
}

// Roving tabindex: exactly one day is tabbable — the focused one, else the
// selected one, else today (when it's in the visible month), else the 1st.
const tabbableIso = computed(() => {
  const visible = cells.value.filter(Boolean)
  const candidates = [
    focusedIso.value,
    selected.value ? toIso(selected.value) : '',
    todayDate.value ? toIso(todayDate.value) : '',
  ]

  return (
    candidates.find(iso => iso && visible.some(cell => cell.iso === iso)) ??
    visible[0]?.iso
  )
})

const KEY_OFFSETS = {
  ArrowLeft: -1,
  ArrowRight: 1,
  ArrowUp: -7,
  ArrowDown: 7,
}

async function onGridKeydown(event) {
  const current = parseIso(tabbableIso.value)

  if (!current) {
    return
  }

  let next = null

  if (event.key in KEY_OFFSETS) {
    next = addDays(current, KEY_OFFSETS[event.key])
  } else if (event.key === 'Home') {
    next = addDays(current, -weekdayOf(current))
  } else if (event.key === 'End') {
    next = addDays(current, 6 - weekdayOf(current))
  } else if (event.key === 'PageUp' || event.key === 'PageDown') {
    const index =
      current.year * 12 +
      (current.month - 1) +
      (event.key === 'PageUp' ? -1 : 1)
    const year = Math.floor(index / 12)
    const month = (index % 12) + 1

    next = {
      year,
      month,
      day: Math.min(current.day, daysInMonth(year, month)),
    }
  }

  if (!next) {
    return
  }

  event.preventDefault()
  focusedIso.value = toIso(next)
  showMonth(next)
  await nextTick()
  focusDay()
}

function onPopoverKeydown(event) {
  if (event.key === 'Escape') {
    event.stopPropagation()
    closePopover({ restoreFocus: true })
  }
}

function onOutsidePointerDown(event) {
  if (
    popover.value?.contains(event.target) ||
    trigger.value?.contains(event.target)
  ) {
    return
  }

  closePopover()
}

function onViewportChange(event) {
  // Scrolling inside the popover itself must not move or dismiss it.
  if (popover.value?.contains(event.target)) {
    return
  }

  const rect = trigger.value?.getBoundingClientRect()

  // The popover is `position: fixed`, so follow the trigger while the page or
  // the table scrolls, and dismiss once the trigger has scrolled out of view.
  if (!rect || rect.bottom < 0 || rect.top > window.innerHeight) {
    closePopover()

    return
  }

  placePopover()
}

watch(open, isOpen => {
  const method = isOpen ? 'addEventListener' : 'removeEventListener'

  document[method]('pointerdown', onOutsidePointerDown, true)
  window[method]('scroll', onViewportChange, true)
  window[method]('resize', onViewportChange)
})

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onOutsidePointerDown, true)
  window.removeEventListener('scroll', onViewportChange, true)
  window.removeEventListener('resize', onViewportChange)
})
</script>

<template>
  <div :class="variant === 'cell' ? 'h-full w-full' : 'inline-block'">
    <input v-if="name" type="hidden" :name="name" :value="value" />

    <button
      ref="trigger"
      type="button"
      v-bind="$attrs"
      class="cursor-pointer whitespace-nowrap text-theme-900 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:text-zinc-100"
      :class="[
        variant === 'cell'
          ? 'm-0 h-full w-full px-2 py-2 text-center text-xs focus:ring-inset'
          : 'rounded border border-theme-200 bg-white px-3 py-1 text-sm dark:border-zinc-700 dark:bg-zinc-900',
        { 'text-gray-400 print:text-transparent': !selected },
      ]"
      :aria-label="`${label}：${selected ? describe(selected) : placeholder}`"
      :title="buttonTitle"
      aria-haspopup="dialog"
      :aria-expanded="open"
      data-testid="date-field-trigger"
      @click="open ? closePopover() : openPopover()"
    >
      {{ buttonText }}
    </button>

    <Teleport to="body">
      <div
        v-if="open"
        ref="popover"
        role="dialog"
        :aria-label="`選擇${label}`"
        class="fixed z-50 rounded-lg border border-theme-200 bg-white p-3 text-theme-900 shadow-lg dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 print:hidden"
        :style="popoverStyle"
        data-testid="date-field-popover"
        @keydown="onPopoverKeydown"
      >
        <div class="mb-2 flex items-center justify-between">
          <button
            type="button"
            class="flex size-8 items-center justify-center rounded text-lg hover:bg-theme-100 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:hover:bg-zinc-800"
            aria-label="上個月"
            data-testid="date-field-prev"
            @click="shiftMonth(-1)"
          >
            ‹
          </button>
          <div class="text-sm font-semibold" aria-live="polite">
            {{ monthLabel }}
          </div>
          <button
            type="button"
            class="flex size-8 items-center justify-center rounded text-lg hover:bg-theme-100 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:hover:bg-zinc-800"
            aria-label="下個月"
            data-testid="date-field-next"
            @click="shiftMonth(1)"
          >
            ›
          </button>
        </div>

        <div
          class="grid grid-cols-7 text-center text-xs text-theme-500 dark:text-zinc-400"
        >
          <div v-for="weekday in WEEKDAYS" :key="weekday" class="py-1">
            {{ weekday }}
          </div>
        </div>

        <div class="grid grid-cols-7 gap-y-0.5" @keydown="onGridKeydown">
          <template v-for="(cell, index) in cells" :key="index">
            <div v-if="!cell"></div>
            <button
              v-else
              type="button"
              data-day
              :data-date="cell.iso"
              :tabindex="cell.iso === tabbableIso ? 0 : -1"
              :aria-label="describe(cell.date)"
              :aria-pressed="cell.iso === value"
              class="mx-auto flex size-8 items-center justify-center rounded-full text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
              :class="
                cell.iso === value
                  ? 'bg-theme-700 font-semibold text-white'
                  : [
                      'hover:bg-theme-100 dark:hover:bg-zinc-800',
                      todayDate && cell.iso === toIso(todayDate)
                        ? 'font-semibold text-blue-600 ring-1 ring-blue-400 dark:text-blue-400'
                        : '',
                    ]
              "
              @click="pick(cell.date)"
            >
              {{ cell.date.day }}
            </button>
          </template>
        </div>

        <div
          class="mt-2 flex justify-between border-t border-theme-100 pt-2 dark:border-zinc-800"
        >
          <button
            v-if="clearable"
            type="button"
            class="rounded px-2 py-1 text-xs text-theme-700 hover:bg-theme-100 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:text-zinc-300 dark:hover:bg-zinc-800"
            data-testid="date-field-clear"
            @click="clear()"
          >
            清除
          </button>
          <button
            v-if="todayDate"
            type="button"
            class="rounded px-2 py-1 text-xs text-theme-700 hover:bg-theme-100 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:text-zinc-300 dark:hover:bg-zinc-800"
            data-testid="date-field-today"
            @click="pickToday()"
          >
            今天
          </button>
        </div>
      </div>
    </Teleport>
  </div>
</template>
