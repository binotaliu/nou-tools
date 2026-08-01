<x-layout
    title="送出成功 - NOU 小幫手"
    description="已收到您送出的優惠店家資訊，將由管理員確認。"
    no-index
>
    <div class="mx-auto max-w-2xl">
        <x-card>
            <div class="flex flex-col items-center gap-4 py-8 text-center">
                <x-heroicon-o-check-circle class="size-16 text-green-500" />

                <h2 class="text-2xl font-bold text-warm-900 dark:text-zinc-100">
                    已收到您送出的資料
                </h2>

                <p class="max-w-md text-sm text-warm-600 dark:text-zinc-400">
                    @if ($storeName)
                        感謝您提供的「{{ $storeName }}」優惠店家資訊！
                    @else
                        感謝您提供的優惠店家資訊！
                    @endif
                    我們會盡快確認。確認完後即會顯示在店家列表中，請耐心等候。
                </p>

                <div
                    class="mt-4 flex flex-wrap items-center justify-center gap-3"
                >
                    <x-link-button href="{{ route('discount-stores.index') }}">
                        回到優惠店家列表
                    </x-link-button>
                    <x-link-button href="{{ route('discount-stores.create') }}">
                        繼續送出其他店家
                    </x-link-button>
                </div>
            </div>
        </x-card>
    </div>
</x-layout>
