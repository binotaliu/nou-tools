# Inertia + Vue Views Best Practices

## Props Flow Straight From DTOs/ViewModels

`Inertia::render()` takes the same `Spatie\LaravelData\Data`/ViewModel objects that used to go into `view()`. No reshaping into arrays first — pass the ViewModel (or its DTO) directly as a prop and let it serialize.

```php
return Inertia::render('Schedules/Show', [
    'viewModel' => new ScheduleViewModel($schedule),
]);
```

## Pages Live in `resources/js/Pages/{Domain}/{Page}.vue`

Mirror the domain naming from `src/Domains/{Domain}/`. A page component name passed to `Inertia::render()` must match its file path under `resources/js/Pages/`, e.g. `Inertia::render('Schedules/Show', ...)` → `resources/js/Pages/Schedules/Show.vue`.

## Wrap Pages in `AppLayout` for the Persistent Shell

`resources/js/Layouts/AppLayout.vue` is the shared header/nav/footer chrome. Wrap page components in it directly:

```vue
<template>
  <AppLayout>
    <!-- page content -->
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
</script>
```

## Composables Replace Alpine `Alpine.data()` Globals

Stateful, reusable client logic that used to live in `Alpine.data()` (in `resources/js/alpine-components.js`) now lives as a composable under `resources/js/Composables/`. Import and call it inside `<script setup>` instead of registering it globally.

## Inertia Partial Reloads Replace Blade Fragments

Blade's `->fragmentIf()` for htmx/Turbo partial re-renders has no equivalent need in Inertia. Use `router.reload({ only: [...] })` (or `router.visit()` with `only`) to refetch specific props without a full page visit:

```js
import { router } from '@inertiajs/vue3'

router.reload({ only: ['viewModel'] })
```

## Exception: StudyRoom Uses Inertia Only for the Shell

自習室 (StudyRoom) pages render through Inertia for navigation and the initial page shell, but do not push live seat/session state through Inertia props — that state changes too fast for a page-prop model. It's fetched and mutated via the existing REST JSON endpoints and updated live via Echo/Reverb broadcasts instead. Don't route StudyRoom's live state through `Inertia::render()` props or `router.reload()`.
