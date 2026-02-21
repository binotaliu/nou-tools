<x-card {{ $attributes->merge() }}>
    <div class="flex items-baseline justify-between gap-4">
        <div>
            <h2 class="text-3xl font-semibold">
                {{ $greetingText }}，歡迎回來！
            </h2>

            <p class="mt-1 text-warm-500">
                今天是 {{ $dateString }}，{{ $semesterInfo }}
            </p>
        </div>
    </div>
</x-card>
