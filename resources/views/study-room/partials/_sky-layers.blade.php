{{--
    天空本身：漸層底色、WebGL 著色器、星星、日暈、月亮、太陽、雲。
    $skyLayout 是 'garden'（一樓花園上方的那條天空）或 'focus'（專注模式整片螢幕），
    兩者共用同一份天色資料，只差在畫在哪個 canvas、以及日月要畫多大。
--}}
@php
    $isFocus = $skyLayout === 'focus';
    $testPrefix = $isFocus ? 'study-room-focus-' : 'study-room-';
@endphp

{{-- 天空：底層是調色盤漸層 --}}
<div class="absolute inset-0" :style="gardenSkyStyle()"></div>

{{-- 天空著色器：只畫天空，蓋在漸層之上、景物之下，補上大氣散射與日暈。沒有 WebGL 就維持透明，露出底下的漸層 --}}
<canvas
    class="absolute inset-0 size-full transition-opacity duration-700"
    x-init="mountSkyCanvas($el, '{{ $skyLayout }}')"
    :style="skyCanvasStyle('{{ $skyLayout }}')"
    data-testid="{{ $testPrefix }}sky-canvas"
    aria-hidden="true"
></canvas>

{{-- 星星：暮色漸深時浮現 --}}
<div
    class="absolute inset-0 transition-opacity duration-1000"
    :style="starsStyle()"
    data-testid="{{ $testPrefix }}stars"
    aria-hidden="true"
>
    <template x-for="star in starsFor('{{ $skyLayout }}')" :key="star.id">
        <span
            class="absolute animate-twinkle rounded-full bg-white"
            :style="starStyle(star, '{{ $skyLayout }}')"
        ></span>
    </template>
</div>

{{-- 日出日落時貼近地平線的霞光（著色器接手時改由它繪製） --}}
<div
    x-show="!isSkyCanvasActive('{{ $skyLayout }}')"
    class="absolute inset-0 transition-opacity duration-1000"
    :style="sunGlowStyle('{{ $skyLayout }}')"
    aria-hidden="true"
></div>

{{-- 月亮：用一片天空色的圓盤蓋出月相 --}}
<span
    x-show="sky.moonVisible"
    class="absolute -translate-x-1/2 -translate-y-1/2 overflow-hidden rounded-full bg-[radial-gradient(circle_at_38%_35%,#fffdf0_0%,#f3edd0_60%,#d9d2ae_100%)] transition-[top,left] duration-1000 {{ $isFocus ? 'size-16 shadow-[0_0_48px_18px_rgba(255,250,220,0.3)] sm:size-20' : 'size-5 shadow-[0_0_16px_6px_rgba(255,250,220,0.3)]' }}"
    :style="moonStyle('{{ $skyLayout }}')"
    data-testid="{{ $testPrefix }}moon"
    aria-hidden="true"
>
    <span
        class="absolute top-[30%] left-[55%] rounded-full bg-black/10 {{ $isFocus ? 'size-[18%]' : 'size-1.5' }}"
    ></span>
    <span
        class="absolute top-[58%] left-[28%] rounded-full bg-black/10 {{ $isFocus ? 'size-[12%]' : 'size-1' }}"
    ></span>
    <span
        class="absolute inset-0 rounded-full"
        :style="moonShadowStyle()"
    ></span>
</span>

{{-- 太陽 --}}
<span
    x-show="sky.sunVisible"
    class="absolute -translate-x-1/2 -translate-y-1/2 rounded-full bg-[radial-gradient(circle,#fff8c9_0%,#ffd84a_55%,#ffb03a_100%)] transition-[top,left] duration-1000 {{ $isFocus ? 'size-20 shadow-[0_0_90px_40px_rgba(255,214,90,0.45)] sm:size-28' : 'size-7 shadow-[0_0_28px_12px_rgba(255,214,90,0.45)]' }}"
    :style="sunStyle('{{ $skyLayout }}')"
    data-testid="{{ $testPrefix }}sun"
    aria-hidden="true"
></span>

{{-- 雲：慢慢飄過 --}}
<div
    class="absolute inset-x-0 top-0 h-[55%] opacity-(--g-cloud-opacity) transition-opacity duration-1000"
    aria-hidden="true"
>
    <div
        class="absolute top-[18%] left-[12%] h-3 w-16 animate-drift [animation-duration:150s] {{ $isFocus ? 'scale-[2.5] sm:scale-[3.5]' : '' }}"
    >
        <span
            class="absolute right-0 bottom-0 left-0 h-2.5 rounded-full bg-(--g-cloud)"
        ></span>
        <span
            class="absolute bottom-0.5 left-3 size-4 rounded-full bg-(--g-cloud)"
        ></span>
        <span
            class="absolute bottom-0.5 left-7 size-3 rounded-full bg-(--g-cloud)"
        ></span>
    </div>
    <div
        class="absolute top-[42%] left-[58%] h-2.5 w-12 animate-drift [animation-duration:210s] [animation-delay:-90s] {{ $isFocus ? 'scale-[2.5] sm:scale-[3.5]' : '' }}"
    >
        <span
            class="absolute right-0 bottom-0 left-0 h-2 rounded-full bg-(--g-cloud)"
        ></span>
        <span
            class="absolute bottom-0.5 left-2 size-3 rounded-full bg-(--g-cloud)"
        ></span>
        <span
            class="absolute bottom-0.5 left-5 size-2.5 rounded-full bg-(--g-cloud)"
        ></span>
    </div>
    <div
        class="absolute top-[8%] left-[78%] h-3.5 w-20 animate-drift [animation-duration:180s] [animation-delay:-140s] {{ $isFocus ? 'scale-[2.5] sm:scale-[3.5]' : '' }}"
    >
        <span
            class="absolute right-0 bottom-0 left-0 h-3 rounded-full bg-(--g-cloud)"
        ></span>
        <span
            class="absolute bottom-0.5 left-4 size-5 rounded-full bg-(--g-cloud)"
        ></span>
        <span
            class="absolute bottom-0.5 left-10 size-4 rounded-full bg-(--g-cloud)"
        ></span>
    </div>
    @if ($isFocus)
        <div
            class="absolute top-[30%] left-[32%] h-3 w-14 scale-[2.5] animate-drift [animation-delay:-40s] [animation-duration:240s] sm:scale-[3.5]"
        >
            <span
                class="absolute right-0 bottom-0 left-0 h-2.5 rounded-full bg-(--g-cloud)"
            ></span>
            <span
                class="absolute bottom-0.5 left-2 size-3.5 rounded-full bg-(--g-cloud)"
            ></span>
            <span
                class="absolute bottom-0.5 left-6 size-3 rounded-full bg-(--g-cloud)"
            ></span>
        </div>
    @endif
</div>
