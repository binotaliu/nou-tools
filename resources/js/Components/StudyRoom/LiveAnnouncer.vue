<script setup>
// The two always-mounted live regions behind useStudyRoomAnnouncer.
defineProps({
  announcer: { type: Object, required: true },
})
</script>

<template>
  <div class="sr-only">
    <div role="status" data-testid="study-room-announcer-polite">
      {{ announcer.politeMessage }}
    </div>
    <div role="alert" data-testid="study-room-announcer-assertive">
      {{ announcer.assertiveMessage }}
    </div>
  </div>
</template>
