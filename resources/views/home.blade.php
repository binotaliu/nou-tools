@extends('layouts.app')

@section('title', '首頁')

@section('content')
<div class="space-y-8">
    <x-greeting />


    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <x-card>
            <h4 class="font-medium mb-3">功能選單</h4>

            <a href="{{ route('schedules.create') }}" class="inline-flex items-center px-4 py-2 bg-warm-700 text-white rounded hover:bg-warm-800 gap-2">
                <x-heroicon-o-table-cells class="size-4" />

                建立我的課表
            </a>

            @if(isset($previousSchedule))
                <div class="mt-3 text-sm text-warm-600 w-full">
                    <a
                        href="{{ route('schedules.show', $previousSchedule['token']) }}"
                        class="inline-flex items-center gap-3 w-full bg-warm-50 border border-warm-200 text-warm-700 text-center px-3 py-2 rounded hover:bg-warm-100 transition"
                    >
                        <div class="truncate max-w-xs font-medium text-warm-800">{{ $previousSchedule['name'] ?? '（未命名）' }}</div>
                    </a>
                </div>
            @endif
        </x-card>

        <x-common-links />
    </div>

    {{-- School Calendar --}}
    <x-school-calendar :schedule-events="$scheduleEvents ?? []" :countdown-event="$countdownEvent ?? null" />

    <!-- 今日面授 -->
    <x-card x-data="{ date: '{{ $selectedDate }}' }">
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
                        <div class="space-y-2 ml-2 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                            @php
                                $typeLabels = [
                                    'morning' => '上午班',
                                    'afternoon' => '下午班',
                                    'evening' => '夜間班',
                                    'full_remote' => '全遠距',
                                    'micro_credit' => '微學分',
                                ];
                                $grouped = $course->classes->groupBy('type');
                            @endphp

                            @foreach ($typeLabels as $typeKey => $label)
                                @if (isset($grouped[$typeKey]) && $grouped[$typeKey]->isNotEmpty())
                                    <div class="flex flex-col gap-2 items-stretch">
                                        <div class="text-sm font-semibold text-warm-700">{{ $label }}</div>

                                        @php
                                            // group classes by start/end time so we show the time once per time slot
                                            // If there's a schedule override for today, use that instead
                                            $timeGroups = $grouped[$typeKey]->groupBy(function ($c) use ($selectedDate) {
                                                $todaySchedule = $c->schedules->first();
                                                if ($todaySchedule && $todaySchedule->start_time && $todaySchedule->end_time) {
                                                    return $todaySchedule->start_time.' - '.$todaySchedule->end_time;
                                                }
                                                return $c->start_time ? $c->start_time.' - '.$c->end_time : '時間未定';
                                            });
                                        @endphp

                                        <div class="flex gap-1 w-full">
                                            @foreach ($timeGroups as $timeLabel => $classesAtTime)
                                                <div class="border rounded border-warm-800 p-3 bg-white w-full">
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

                                                    <div class="flex w-full gap-2">
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
    </x-card>


</div>
@endsection
