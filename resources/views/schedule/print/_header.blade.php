{{-- Site mark (same book-open + name as the navbar and the og-image card) with
the site address, repeated on both halves so each piece stays identifiable
once the sheet is cut. --}}
<a
    href="{{ url('/') }}"
    class="flex items-center gap-2 text-theme-700 no-underline"
>
    <x-heroicon-o-book-open class="size-6 shrink-0" />
    <span class="text-[13pt] leading-none font-bold">NOU 小幫手</span>
    <span
        class="text-[8pt] text-zinc-500"
        >{{ parse_url(config('app.url'), PHP_URL_HOST) }}</span
    >
</a>
