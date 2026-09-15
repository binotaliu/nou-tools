<script setup>
// Thin wrapper around @heroicons/vue's 24px outline set, mirroring the
// server-side blade-ui-kit/blade-heroicons `o-*` icons used throughout the
// existing Blade views (e.g. <x-heroicon-o-book-open />), so Vue templates
// can reference icons by the same short name.
import {
  AcademicCapIcon,
  ArrowLeftIcon,
  ArrowTopRightOnSquareIcon,
  Bars3Icon,
  BookOpenIcon,
  CalendarDaysIcon,
  CheckCircleIcon,
  CheckIcon,
  ChevronDownIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  CodeBracketIcon,
  ComputerDesktopIcon,
  DevicePhoneMobileIcon,
  EnvelopeIcon,
  ExclamationCircleIcon,
  EyeSlashIcon,
  FunnelIcon,
  InboxIcon,
  InformationCircleIcon,
  LinkIcon,
  MapIcon,
  MapPinIcon,
  MegaphoneIcon,
  MoonIcon,
  PhoneIcon,
  PrinterIcon,
  SignalSlashIcon,
  SunIcon,
  TableCellsIcon,
  TagIcon,
  TruckIcon,
  XCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

const ICONS = {
  'academic-cap': AcademicCapIcon,
  'arrow-left': ArrowLeftIcon,
  'arrow-top-right-on-square': ArrowTopRightOnSquareIcon,
  'bars-3': Bars3Icon,
  'book-open': BookOpenIcon,
  'calendar-days': CalendarDaysIcon,
  check: CheckIcon,
  'check-circle': CheckCircleIcon,
  'chevron-down': ChevronDownIcon,
  'chevron-left': ChevronLeftIcon,
  'chevron-right': ChevronRightIcon,
  'code-bracket': CodeBracketIcon,
  'computer-desktop': ComputerDesktopIcon,
  'device-phone-mobile': DevicePhoneMobileIcon,
  envelope: EnvelopeIcon,
  'exclamation-circle': ExclamationCircleIcon,
  'eye-slash': EyeSlashIcon,
  funnel: FunnelIcon,
  inbox: InboxIcon,
  'information-circle': InformationCircleIcon,
  link: LinkIcon,
  map: MapIcon,
  'map-pin': MapPinIcon,
  megaphone: MegaphoneIcon,
  moon: MoonIcon,
  phone: PhoneIcon,
  printer: PrinterIcon,
  'signal-slash': SignalSlashIcon,
  sun: SunIcon,
  'table-cells': TableCellsIcon,
  tag: TagIcon,
  truck: TruckIcon,
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
