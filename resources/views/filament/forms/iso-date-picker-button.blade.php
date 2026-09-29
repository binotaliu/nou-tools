{{-- The native date input is only a calendar popup; the text field stays the source of truth. --}}
<span x-data class="relative inline-flex">
    <input
        type="date"
        tabindex="-1"
        aria-hidden="true"
        x-ref="calendar"
        class="pointer-events-none absolute inset-0 size-full opacity-0"
        x-on:change="if ($event.target.value) { $wire.$set(@js($statePath), $event.target.value); $event.target.blur() }"
    />
    <button
        type="button"
        class="inline-flex text-gray-400 hover:text-gray-600 disabled:cursor-not-allowed dark:hover:text-gray-200"
        aria-label="開啟日曆"
        @disabled($disabled)
        x-on:click="$refs.calendar.showPicker()"
    >
        <x-filament::icon icon="heroicon-o-calendar-days" class="size-5" />
    </button>
</span>
