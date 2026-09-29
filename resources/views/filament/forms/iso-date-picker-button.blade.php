{{--
    A transparent native date input laid over the icon: clicking it opens the
    browser's own calendar, which then closes itself (calling showPicker() from
    a separate button left it open in Safari). The text field stays the source
    of truth. Sizes are inline because the admin panel's stylesheet only
    contains the utilities Filament's own views use, not this app's Tailwind.
--}}
<span
    style="
        position: relative;
        display: inline-flex;
        flex: none;
        width: 1.25rem;
        height: 1.25rem;
    "
>
    <x-filament::icon
        icon="heroicon-o-calendar-days"
        style="width: 1.25rem; height: 1.25rem"
        aria-hidden="true"
    />
    <input
        type="date"
        aria-label="開啟日曆"
        @disabled($disabled)
        style="
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        "
        x-data
        x-on:change="if ($event.target.value) { $wire.$set(@js($statePath), $event.target.value) }"
    />
</span>
