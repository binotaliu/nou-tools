{{--
    共桌旁的一張椅子。$backrest 指定椅背朝向（border-t-4 朝上、border-b-4 朝下），
    $timerSide 指定計時器顯示在椅子的哪一側（top / bottom，永遠背對桌子）。
    椅子太小放不下暱稱與活動，所以滑鼠移入或點一下有人的椅子會彈出小卡片。
--}}
<div
    class="relative flex items-center gap-0.5 {{ $timerSide === 'top' ? 'flex-col-reverse' : 'flex-col' }}"
    @mouseenter="peek(seat)"
    @mouseleave="unpeek(seat)"
    @click.outside="unpeek(seat)"
>
    <button
        type="button"
        @click="tapTableSeat(seat)"
        :disabled="busySeatCode !== null ||
        (!seat.isOccupied && heldSeatCode !== null)"
        :class="seatClasses(seat, 'table') + ' {{ $backrest }}'"
        :data-testid="seatTestId(seat)"
        :aria-label="seatAriaLabel(seat)"
        :aria-expanded="isPeeking(seat) ? 'true' : 'false'"
    >
        <template x-if="!seat.isOccupied">
            <span
                class="text-[10px] font-medium text-warm-400 dark:text-zinc-500"
                x-text="seat.seatNumber"
            ></span>
        </template>
        <template x-if="seat.isOccupied">
            <span class="text-base leading-none" x-text="seat.emoji"></span>
        </template>
        <span
            x-show="isMine(seat)"
            class="absolute -top-1.5 -right-2 rounded-full bg-amber-500 px-1 text-[9px] leading-4 font-semibold text-white shadow-sm"
            >你</span
        >
    </button>

    {{-- Always rendered (not x-show) so the chair's height is reserved
    whether or not the seat is occupied — toggling this line's presence
    on take/leave would shift the table and the row of chairs below it. --}}
    <span
        class="font-mono text-[9px] tabular-nums"
        :class="seat.isOccupied ? seatTimerLabelClass(seat) : 'invisible'"
        x-text="seat.isOccupied ? timerLabel(seat) : '00:00'"
        :data-testid="seatTestId(seat) + '-timer'"
    ></span>

    {{-- 彈出小卡片：暱稱、活動、剩餘時間 --}}
    <div
        x-show="isPeeking(seat)"
        x-cloak
        class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-1.5 w-36 -translate-x-1/2 rounded-lg border border-warm-200 bg-white p-2 text-left shadow-md dark:border-zinc-600 dark:bg-zinc-800"
        :data-testid="seatTestId(seat) + '-popover'"
    >
        <p class="flex items-center gap-1.5 text-xs font-semibold text-warm-900 dark:text-zinc-100">
            <span x-text="seat.emoji"></span>
            <span class="truncate" x-text="seat.nickname"></span>
        </p>
        <p
            class="mt-1 text-[11px] leading-snug text-warm-700 dark:text-zinc-300"
            x-text="thoughtBubbleText(seat)"
        ></p>
        <p
            class="mt-1 font-mono text-[11px] text-warm-500 tabular-nums dark:text-zinc-400"
            x-text="'剩餘 ' + timerLabel(seat)"
        ></p>
        <span
            class="absolute top-full left-1/2 size-2 -translate-x-1/2 -translate-y-1/2 rotate-45 border-r border-b border-warm-200 bg-white dark:border-zinc-600 dark:bg-zinc-800"
            aria-hidden="true"
        ></span>
    </div>
</div>
