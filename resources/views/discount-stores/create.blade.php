<x-layout
    title="新增優惠店家 - NOU 小幫手"
    description="送出新的學生優惠店家資訊。"
>
    <div
        x-data="discountStoreCreateForm({
                    type: @js(old('type', '')),
                    city: @js(old('city', '')),
                    district: @js(old('district', '')),
                    districtsByCity: {{ Js::encode($districtsByCity) }},
                })"
        class="mx-auto max-w-3xl space-y-6"
    >
        <div class="space-y-2">
            <h2 class="text-3xl font-bold text-warm-900 dark:text-zinc-100">
                新增優惠店家
            </h2>
            <p class="text-sm text-warm-600 dark:text-zinc-400">填寫下方表單來送出新的學生優惠店家。送出後需經管理員確認才會顯示在前台。</p>
        </div>

        <x-card>
            <form
                action="{{ route('discount-stores.store') }}"
                method="POST"
                class="space-y-6"
            >
                @csrf

                <div class="space-y-4">
                    <h3
                        class="text-lg font-semibold text-warm-900 dark:text-zinc-100"
                    >
                        基本資料
                    </h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                for="name"
                                class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                            >
                                店家名稱
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name') }}"
                                class="w-full rounded-lg border border-warm-200 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700"
                                placeholder="店家名稱或網站名稱"
                            />
                            @error('name')
                                <p class="mt-1 text-xs text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label
                                for="type"
                                class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                            >
                                類型
                                <span class="text-red-500">*</span>
                            </label>
                            <x-select
                                id="type"
                                name="type"
                                x-model="type"
                                @change="handleTypeChange()"
                            >
                                <option value="">請選擇</option>
                                @foreach ($types as $storeType)
                                    <option
                                        value="{{ $storeType->value }}"
                                        @selected(old('type') === $storeType->value)
                                    >
                                        {{ $storeType->label() }}
                                    </option>
                                @endforeach
                            </x-select>
                            @error('type')
                                <p class="mt-1 text-xs text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label
                                for="category_id"
                                class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                            >
                                分類
                                <span class="text-red-500">*</span>
                            </label>
                            <x-select id="category_id" name="category_id">
                                <option value="">請選擇</option>
                                @foreach ($categories as $category)
                                    <option
                                        value="{{ $category->id }}"
                                        @selected((int) old('category_id') === $category->id)
                                    >
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </x-select>
                            @error('category_id')
                                <p class="mt-1 text-xs text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div x-show="type && type !== 'online'" class="space-y-4">
                    <h3
                        class="text-lg font-semibold text-warm-900 dark:text-zinc-100"
                    >
                        地點資訊
                    </h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                for="city"
                                class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                            >
                                縣市
                                <span
                                    x-show="type === 'local'"
                                    class="text-red-500"
                                >
                                    *
                                </span>
                            </label>
                            <x-select
                                id="city"
                                name="city"
                                x-model="city"
                                @change="handleCityChange()"
                            >
                                <option value="">請選擇</option>
                                @foreach ($cities as $cityName)
                                    <option
                                        value="{{ $cityName }}"
                                        @selected(old('city') === $cityName)
                                    >
                                        {{ $cityName }}
                                    </option>
                                @endforeach
                            </x-select>
                            @error('city')
                                <p class="mt-1 text-xs text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label
                                for="district"
                                class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                            >
                                鄉鎮市區
                                <span
                                    x-show="type === 'local'"
                                    class="text-red-500"
                                >
                                    *
                                </span>
                            </label>
                            <x-select
                                id="district"
                                name="district"
                                x-model="district"
                            >
                                <option value="">請選擇</option>
                                <template
                                    x-for="districtName in districts"
                                    :key="districtName"
                                >
                                    <option
                                        :value="districtName"
                                        x-text="districtName"
                                    ></option>
                                </template>
                            </x-select>
                            @error('district')
                                <p class="mt-1 text-xs text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <label
                        for="address"
                        class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                        x-text="type === 'online' ? '網址' : '詳細地址'"
                    ></label>
                    <input
                        type="text"
                        id="address"
                        name="address"
                        value="{{ old('address') }}"
                        class="w-full rounded-lg border border-warm-200 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700"
                        :placeholder="type === 'online'
                            ? 'https://...'
                            : '詳細地址'"
                    />
                    @error('address')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-4">
                    <h3
                        class="text-lg font-semibold text-warm-900 dark:text-zinc-100"
                    >
                        優惠資訊
                    </h3>
                    <div>
                        <label
                            for="verification_method"
                            class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                        >
                            驗證方式
                        </label>
                        <input
                            type="text"
                            id="verification_method"
                            name="verification_method"
                            value="{{ old('verification_method') }}"
                            class="w-full rounded-lg border border-warm-200 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700"
                            placeholder="例如：學生信箱、學生證、學生證+選課卡"
                        />
                        @error('verification_method')
                            <p class="mt-1 text-xs text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="discount_details"
                            class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                        >
                            優惠內容
                            <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            id="discount_details"
                            name="discount_details"
                            rows="3"
                            class="w-full rounded-lg border border-warm-200 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700"
                            placeholder="描述詳細的優惠內容..."
                            >{{ old('discount_details') }}</textarea
                        >
                        @error('discount_details')
                            <p class="mt-1 text-xs text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="notes"
                            class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                        >
                            備註
                        </label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="2"
                            class="w-full rounded-lg border border-warm-200 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700"
                            placeholder="其他補充說明（選填）"
                            >{{ old('notes') }}</textarea
                        >
                        @error('notes')
                            <p class="mt-1 text-xs text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="flex items-start gap-2">
                        <input
                            type="checkbox"
                            id="tested_valid"
                            name="tested_valid"
                            value="1"
                            @checked(old('tested_valid'))
                            class="mt-0.5 rounded border-warm-300 text-orange-500 focus:ring-orange-300 dark:border-zinc-700"
                        />
                        <span class="text-sm text-warm-700 dark:text-zinc-300">
                            我已實際測試過，確認此優惠資訊正確有效
                        </span>
                    </label>
                    @error('tested_valid')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-turnstile-widget language="zh-tw" />
                    @error('cf-turnstile-response')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <x-button type="submit" variant="warm-dark">
                        <x-heroicon-o-paper-airplane class="size-4" />
                        送出
                    </x-button>
                    <x-link-button
                        :href="route('discount-stores.index')"
                        variant="secondary"
                    >
                        取消
                    </x-link-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layout>
