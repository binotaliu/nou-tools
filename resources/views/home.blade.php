@extends('layouts.app')

@section('title', '首頁')

@section('content')
<div class="space-y-8">
    <!-- greeting -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-warm-200">
        <div class="flex items-baseline justify-between gap-4">
            <div>
                <h2 class="text-3xl font-semibold">{{ $greeting }}，歡迎回來！</h2>
                @php
                    $__selectedDate = \Carbon\Carbon::parse($selectedDate, 'Asia/Taipei');
                    $__weekdayMap = ['日', '一', '二', '三', '四', '五', '六'];
                @endphp
                <p class="mt-1 text-warm-500">{{ $__selectedDate->format('Y年m月d日') }}（{{ $__weekdayMap[$__selectedDate->dayOfWeek] }}）</p>
            </div>
        </div>
    </div>

    <!-- 今日面授 -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-warm-200" x-data="{ date: '{{ $selectedDate }}' }">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium">今日視訊面授</h3>
            <div class="flex items-center gap-3">
                <label class="text-sm text-warm-500">選擇日期</label>
                <input type="date" class="border rounded px-3 py-1 text-sm" x-model="date" @change="window.location = `?date=${date}`" :value="date">
            </div>
        </div>

        <div class="mt-4 space-y-6">
            @if ($courses->isEmpty())
                <div class="text-sm text-warm-500">今日無面授課程。</div>
            @else
                @foreach ($courses as $course)
                    <div>
                        <h4 class="font-semibold text-warm-800 mb-3">{{ $course->name }}</h4>
                        <div class="space-y-2 ml-2">
                            @php
                                $typeLabels = [
                                    'morning' => '上午班',
                                    'afternoon' => '下午班',
                                    'evening' => '夜間班',
                                    'full_remote' => '全遠距 / 微學分',
                                ];
                                $grouped = $course->classes->groupBy('type');
                            @endphp

                            @foreach ($typeLabels as $typeKey => $label)
                                @if (isset($grouped[$typeKey]) && $grouped[$typeKey]->isNotEmpty())
                                    <div class="mb-4">
                                        <div class="text-sm font-semibold text-warm-700 mb-2">{{ $label }}</div>

                                        @php
                                            // group classes by start/end time so we show the time once per time slot
                                            $timeGroups = $grouped[$typeKey]->groupBy(function ($c) {
                                                return $c->start_time ? $c->start_time.' - '.$c->end_time : '時間未定';
                                            });
                                        @endphp

                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                            @foreach ($timeGroups as $timeLabel => $classesAtTime)
                                                <div class="border rounded p-3 bg-white">
                                                    <div class="text-sm text-warm-600 font-medium mb-3">{{ $timeLabel }}</div>

                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                        @foreach ($classesAtTime as $courseClass)
                                                            @if ($courseClass->link)
                                                                <a href="{{ $courseClass->link }}" target="_blank" rel="noopener noreferrer" class="block w-full text-left px-4 py-3 rounded border border-orange-200 bg-orange-50 text-orange-700 hover:bg-orange-100 transition">
                                                                    <div class="text-lg font-semibold">{{ $courseClass->code ?? '—' }}</div>
                                                                    @if ($courseClass->teacher_name)
                                                                        <div class="text-sm text-warm-600 truncate mt-1">{{ $courseClass->teacher_name }}</div>
                                                                    @endif
                                                                </a>
                                                            @else
                                                                <div class="block w-full text-left px-4 py-3 rounded border bg-gray-50 text-warm-500">
                                                                    <div class="text-lg font-semibold">{{ $courseClass->code ?? '—' }}</div>
                                                                    @if ($courseClass->teacher_name)
                                                                        <div class="text-sm text-warm-600 truncate mt-1">{{ $courseClass->teacher_name }}</div>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach

                            @php
                                $known = array_keys($typeLabels);
                                $others = $grouped->reject(function ($group, $key) use ($known) { return in_array($key, $known); });
                            @endphp

                            @if ($others->isNotEmpty())
                                <div class="mb-4">
                                    <div class="text-sm font-semibold text-warm-700 mb-2">其他</div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        @foreach ($others as $otherGroup)
                                            @php
                                                $timeGroups = $otherGroup->groupBy(function ($c) {
                                                    return $c->start_time ? $c->start_time.' - '.$c->end_time : '時間未定';
                                                });
                                            @endphp

                                            @foreach ($timeGroups as $timeLabel => $classesAtTime)
                                                <div class="border rounded p-3 bg-white">
                                                    <div class="text-sm text-warm-600 font-medium mb-3">{{ $timeLabel }}</div>

                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                        @foreach ($classesAtTime as $courseClass)
                                                            @if ($courseClass->link)
                                                                <a href="{{ $courseClass->link }}" target="_blank" rel="noopener noreferrer" class="block w-full text-left px-4 py-3 rounded border border-orange-200 bg-orange-50 text-orange-700 hover:bg-orange-100 transition">
                                                                    <div class="text-lg font-semibold">{{ $courseClass->code ?? '—' }}</div>
                                                                    @if ($courseClass->teacher_name)
                                                                        <div class="text-sm text-warm-600 truncate mt-1">{{ $courseClass->teacher_name }}</div>
                                                                    @endif
                                                                </a>
                                                            @else
                                                                <div class="block w-full text-left px-4 py-3 rounded border bg-gray-50 text-warm-500">
                                                                    <div class="text-lg font-semibold">{{ $courseClass->code ?? '—' }}</div>
                                                                    @if ($courseClass->teacher_name)
                                                                        <div class="text-sm text-warm-600 truncate mt-1">{{ $courseClass->teacher_name }}</div>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <!-- 建立我的課表 -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-warm-200">
        <a href="{{ route('schedule.create') }}" class="inline-block px-4 py-2 bg-warm-700 text-white rounded hover:bg-warm-800">建立我的課表</a>
    </div>

    <!-- 常用連結 -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-warm-200">
        <h4 class="font-medium mb-3">常用連結</h4>
        <ul class="space-y-2 text-warm-700">
            <li><a class="underline hover:text-warm-900" href="https://www.nou.edu.tw" target="_blank">學校官網</a></li>
            <li><a class="underline hover:text-warm-900" href="https://noustud.nou.edu.tw/" target="_blank">教務行政資訊系統</a></li>
            <li><a class="underline hover:text-warm-900" href="https://uu.nou.edu.tw/" target="_blank">數位學習平台 (UU平台)</a></li>
        </ul>
    </div>
</div>
@endsection
