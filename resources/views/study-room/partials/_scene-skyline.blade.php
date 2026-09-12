{{--
    遠處的城市天際線：窗戶到了晚上會亮起來。
    $idPrefix 讓 pattern id 在同一頁出現兩次（花園與專注模式）時不撞名；$class 決定擺在哪。
--}}
<svg
    class="{{ $class }}"
    viewBox="0 0 1000 100"
    preserveAspectRatio="none"
    aria-hidden="true"
>
    <defs>
        <pattern
            id="{{ $idPrefix }}-city-windows-far"
            width="12"
            height="14"
            patternUnits="userSpaceOnUse"
        >
            <rect x="3" y="3" width="4" height="5" fill="var(--g-windowDim)" />
        </pattern>
        <pattern
            id="{{ $idPrefix }}-city-windows-near"
            width="16"
            height="16"
            patternUnits="userSpaceOnUse"
        >
            <rect x="2" y="3" width="5" height="6" fill="var(--g-windowLit)" />
            <rect x="10" y="3" width="4" height="6" fill="var(--g-windowDim)" />
        </pattern>
    </defs>
    {{-- 最遠的一排，中間是城裡最高的那座塔 --}}
    <g fill="var(--g-buildingFar)">
        <rect x="0" y="52" width="60" height="60" />
        <rect x="70" y="36" width="34" height="80" />
        <rect x="112" y="58" width="50" height="60" />
        <rect x="170" y="44" width="28" height="70" />
        <rect x="206" y="30" width="44" height="90" />
        <rect x="258" y="56" width="36" height="60" />
        <rect x="302" y="40" width="26" height="80" />
        <rect x="336" y="62" width="60" height="50" />
        <rect x="404" y="34" width="30" height="80" />
        <rect x="442" y="50" width="44" height="70" />
        <rect x="530" y="46" width="40" height="70" />
        <rect x="578" y="60" width="34" height="60" />
        <rect x="620" y="38" width="30" height="80" />
        <rect x="658" y="54" width="56" height="60" />
        <rect x="722" y="42" width="26" height="80" />
        <rect x="756" y="58" width="44" height="60" />
        <rect x="808" y="32" width="36" height="90" />
        <rect x="852" y="50" width="30" height="70" />
        <rect x="890" y="60" width="52" height="60" />
        <rect x="950" y="44" width="50" height="70" />
        <path d="M498 100 L498 30 L504 30 L504 12 L512 12 L512 30 L518 30 L518 100 Z" />
    </g>
    <g fill="url(#{{ $idPrefix }}-city-windows-far)" opacity="0.7">
        <rect x="70" y="36" width="34" height="80" />
        <rect x="206" y="30" width="44" height="90" />
        <rect x="302" y="40" width="26" height="80" />
        <rect x="404" y="34" width="30" height="80" />
        <rect x="620" y="38" width="30" height="80" />
        <rect x="722" y="42" width="26" height="80" />
        <rect x="808" y="32" width="36" height="90" />
    </g>
    {{-- 近一點的樓房 --}}
    <g fill="var(--g-buildingNear)">
        <rect x="20" y="66" width="70" height="50" />
        <rect x="130" y="72" width="50" height="40" />
        <rect x="230" y="60" width="40" height="60" />
        <rect x="320" y="70" width="80" height="50" />
        <rect x="450" y="64" width="36" height="60" />
        <rect x="540" y="74" width="60" height="40" />
        <rect x="640" y="62" width="44" height="60" />
        <rect x="730" y="70" width="70" height="50" />
        <rect x="850" y="66" width="46" height="60" />
        <rect x="930" y="74" width="70" height="40" />
    </g>
    <g fill="url(#{{ $idPrefix }}-city-windows-near)">
        <rect x="20" y="66" width="70" height="50" />
        <rect x="130" y="72" width="50" height="40" />
        <rect x="230" y="60" width="40" height="60" />
        <rect x="320" y="70" width="80" height="50" />
        <rect x="450" y="64" width="36" height="60" />
        <rect x="540" y="74" width="60" height="40" />
        <rect x="640" y="62" width="44" height="60" />
        <rect x="730" y="70" width="70" height="50" />
        <rect x="850" y="66" width="46" height="60" />
        <rect x="930" y="74" width="70" height="40" />
    </g>
</svg>
