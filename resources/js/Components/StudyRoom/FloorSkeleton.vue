<script setup>
// Renders floor 1's seat grid at the right size before GET /study-room/state
// resolves, so the page doesn't jump from empty to full height once real
// occupancy data arrives. Floor 1 is always open (see
// ResolveOpenFloorCount), so it's the only floor safe to guess at — showing
// more would risk shrinking back down if fewer floors turn out to be open.
// Sizing mirrors the real floor markup in Pages/StudyRoom/Show.vue; content
// is inert placeholders only, never real seat data.
defineProps({
  soloSeatsPerFloor: { type: Number, required: true },
  tablesPerFloor: { type: Number, required: true },
  seatsPerTable: { type: Number, required: true },
})

function topRowCount(seatsPerTable) {
  return Math.ceil(seatsPerTable / 2)
}

function bottomRowCount(seatsPerTable) {
  return seatsPerTable - topRowCount(seatsPerTable)
}
</script>

<template>
  <section class="space-y-3" data-testid="study-room-floor-skeleton">
    <div class="flex items-end justify-between px-1">
      <div
        class="h-7 w-20 animate-pulse rounded bg-warm-200 dark:bg-zinc-800"
      ></div>
      <div
        class="h-6 w-28 animate-pulse rounded-full bg-warm-100 dark:bg-zinc-800"
      ></div>
    </div>

    <div
      class="relative rounded-2xl border-[6px] border-warm-300 bg-warm-100/60 shadow-sm dark:border-zinc-600 dark:bg-zinc-900"
    >
      <div class="relative space-y-6 rounded-[10px] px-4 pt-6 pb-16 sm:px-8">
        <div
          class="grid grid-cols-3 justify-items-center gap-x-3 gap-y-7 sm:grid-cols-4 sm:gap-x-5 md:grid-cols-6"
        >
          <div
            v-for="n in soloSeatsPerFloor"
            :key="n"
            class="min-h-[99px] w-full max-w-24 animate-pulse rounded-t-lg bg-warm-200/70 dark:bg-zinc-800/70"
          ></div>
        </div>

        <div class="flex flex-wrap justify-center gap-x-8 gap-y-6 pt-2">
          <div
            v-for="table in tablesPerFloor"
            :key="table"
            class="flex flex-col items-center gap-1"
          >
            <div class="flex gap-4">
              <div
                v-for="seat in topRowCount(seatsPerTable)"
                :key="seat"
                class="size-9 animate-pulse rounded-lg bg-warm-200/70 dark:bg-zinc-800/70"
              ></div>
            </div>

            <div
              class="h-14 w-44 animate-pulse rounded-xl bg-warm-100/70 dark:bg-zinc-800/50"
            ></div>

            <div class="flex gap-4">
              <div
                v-for="seat in bottomRowCount(seatsPerTable)"
                :key="seat"
                class="size-9 animate-pulse rounded-lg bg-warm-200/70 dark:bg-zinc-800/70"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
