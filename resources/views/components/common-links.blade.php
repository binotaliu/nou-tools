<x-card class="w-full md:w-auto">
    <h4 class="font-medium mb-3">常用連結</h4>
    <div class="flex flex-col md:flex-row md:items-center gap-2">
        <x-external-link href="https://www.nou.edu.tw">
            <x-slot:icon>
                <x-heroicon-o-academic-cap class="size-4" />
            </x-slot:icon>
            學校官網
        </x-external-link>

        <x-external-link href="https://noustud.nou.edu.tw/">
            <x-slot:icon>
                <x-heroicon-o-computer-desktop class="size-4" />
            </x-slot:icon>
            教務行政資訊系統
        </x-external-link>

        <x-external-link href="https://uu.nou.edu.tw/">
            <x-slot:icon>
                <x-heroicon-o-globe-alt class="size-4" />
            </x-slot:icon>
            數位學習平台 (UU平台)
        </x-external-link>
    </div>
</x-card>
