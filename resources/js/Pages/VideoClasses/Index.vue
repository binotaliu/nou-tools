<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import VideoCourses from '../../Components/Home/VideoCourses.vue'

defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <Head title="今日視訊面授 - NOU 小幫手" />

  <AppLayout>
    <div class="mx-auto max-w-3xl">
      <VideoCourses
        :courses="viewModel.courses"
        :selected-date="viewModel.selectedDate"
        :today="viewModel.today"
      />
    </div>
  </AppLayout>
</template>
