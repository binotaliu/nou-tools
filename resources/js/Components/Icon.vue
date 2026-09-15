<script setup>
// Thin wrapper around @heroicons/vue's 24px outline set, mirroring the
// server-side blade-ui-kit/blade-heroicons `o-*` icons used throughout the
// existing Blade views (e.g. <x-heroicon-o-book-open />), so Vue templates
// can reference icons by the same short name.
import {
  AcademicCapIcon,
  Bars3Icon,
  BookOpenIcon,
  CalendarDaysIcon,
  CheckCircleIcon,
  ChevronDownIcon,
  CodeBracketIcon,
  ComputerDesktopIcon,
  DevicePhoneMobileIcon,
  EnvelopeIcon,
  ExclamationCircleIcon,
  InformationCircleIcon,
  LinkIcon,
  MapIcon,
  MegaphoneIcon,
  MoonIcon,
  SunIcon,
  TableCellsIcon,
  TagIcon,
  XCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

const ICONS = {
  'academic-cap': AcademicCapIcon,
  'bars-3': Bars3Icon,
  'book-open': BookOpenIcon,
  'calendar-days': CalendarDaysIcon,
  'check-circle': CheckCircleIcon,
  'chevron-down': ChevronDownIcon,
  'code-bracket': CodeBracketIcon,
  'computer-desktop': ComputerDesktopIcon,
  'device-phone-mobile': DevicePhoneMobileIcon,
  envelope: EnvelopeIcon,
  'exclamation-circle': ExclamationCircleIcon,
  'information-circle': InformationCircleIcon,
  link: LinkIcon,
  map: MapIcon,
  megaphone: MegaphoneIcon,
  moon: MoonIcon,
  sun: SunIcon,
  'table-cells': TableCellsIcon,
  tag: TagIcon,
  'x-circle': XCircleIcon,
  'x-mark': XMarkIcon,
}

defineProps({
  name: {
    type: String,
    required: true,
  },
})
</script>

<template>
  <component :is="ICONS[name]" aria-hidden="true" />
</template>
