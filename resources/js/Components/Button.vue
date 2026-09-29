<script setup>
// The app's shared button. Attributes (`data-testid`, `accesskey`,
// `aria-label`, `@click`, extra layout classes such as `w-full`) fall through to
// the single root `<button>`.
//
// `disabled` is the native attribute. `ariaDisabled` keeps the button
// focusable (used where a disabled button would drop focus) and swallows the
// click instead. `pressed` renders `aria-pressed` and gives the outline variant
// its selected look.
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'outline',
    validator: value =>
      [
        'primary',
        'outline',
        'tonal',
        'danger',
        'ghost',
        'link',
        'icon',
      ].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: value => ['sm', 'md', 'lg'].includes(value),
  },
  type: {
    type: String,
    default: 'button',
  },
  pill: {
    type: Boolean,
    default: false,
  },
  pressed: {
    type: Boolean,
    default: null,
  },
  ariaDisabled: {
    type: Boolean,
    default: false,
  },
})

const base =
  'inline-flex items-center justify-center gap-2 font-semibold transition disabled:opacity-50 aria-disabled:opacity-50'

const variants = {
  primary:
    'border border-theme-700 bg-theme-700 text-white hover:bg-theme-800 dark:border-theme-500 dark:bg-theme-500 dark:text-zinc-950 dark:hover:bg-theme-400',
  outline:
    'border border-theme-500 bg-white text-theme-900 hover:bg-theme-50 dark:border-zinc-500 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800',
  tonal:
    'border border-theme-200 bg-theme-200 text-theme-900 hover:bg-theme-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700',
  danger:
    'border border-red-100 bg-red-100 text-red-700 hover:bg-red-200 dark:border-red-900 dark:bg-red-950 dark:text-red-300 dark:hover:bg-red-900',
  ghost:
    'text-theme-800 hover:bg-theme-100 dark:text-zinc-200 dark:hover:bg-zinc-800',
  link: 'text-theme-700 hover:underline dark:text-theme-300',
  icon: 'text-theme-700 hover:bg-theme-100 dark:text-zinc-400 dark:hover:bg-zinc-800',
}

// `sm` is the compact 36px control; `icon` squares follow the same steps.
const sizes = {
  sm: 'h-9 px-3 text-sm',
  md: 'px-4 py-2',
  lg: 'px-6 py-3 text-lg',
}

const iconSizes = {
  sm: 'size-8 pointer-coarse:size-11',
  md: 'size-10',
  lg: 'size-12',
}

const classes = computed(() => [
  base,
  variants[props.variant],
  props.variant === 'link'
    ? ''
    : props.variant === 'icon'
      ? iconSizes[props.size]
      : sizes[props.size],
  props.pill ? 'rounded-full' : 'rounded-lg',
  props.pressed && props.variant === 'outline'
    ? 'border-theme-700 bg-theme-100 dark:border-theme-300 dark:bg-zinc-800'
    : '',
])

function onClick(event) {
  if (props.ariaDisabled) {
    event.preventDefault()
    event.stopImmediatePropagation()
  }
}
</script>

<template>
  <button
    :type="type"
    :class="classes"
    :aria-pressed="pressed"
    :aria-disabled="ariaDisabled || undefined"
    @click="onClick"
  >
    <slot />
  </button>
</template>
