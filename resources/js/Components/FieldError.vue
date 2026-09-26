<script setup>
// Validation message tied to its field: give the field
// `:aria-invalid` and `:aria-describedby="id"` (see useFormErrorFocus), and
// render this beside it. `role="alert"` makes screen readers announce it the
// moment it appears.
defineProps({
  id: { type: String, required: true },
  message: { type: String, default: '' },
})
</script>

<template>
  <p
    v-if="message"
    :id="id"
    role="alert"
    class="mt-1 text-xs text-red-600 dark:text-red-400"
  >
    {{ message }}
  </p>
</template>
