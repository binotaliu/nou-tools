<x-card {{ $attributes->class(['print:hidden'])->merge() }}>
    <div class="flex flex-col justify-between gap-1">
        <heading class="text-xl font-semibold sm:text-2xl md:text-3xl">
            {{ $greetingText }}，歡迎回來！
        </heading>

        <p class="text-warm-500">
            今天是 {{ $dateString }}，{{ $semesterInfo }}
        </p>
    </div>
</x-card>
