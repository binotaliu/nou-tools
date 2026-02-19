@extends('layouts.app')

@section('title', $course->name)

@section('content')
    <div class="max-w-5xl mx-auto">
        <div class="mb-8">
            <a href="{{ isset($previousSchedule) ? route('schedules.show', $previousSchedule['token']) : url()->previous() }}" class="text-orange-600 hover:text-orange-700 font-semibold mb-4 inline-flex items-center gap-1">
                <x-heroicon-o-chevron-left class="size-4" />
                回到我的課表
            </a>
            <h2 class="text-3xl font-bold text-warm-900 mb-2">{{ $course->name }}</h2>

            @if(!empty($semesterDisplay))
                <div class="text-sm text-warm-600 mb-4">{{ $semesterDisplay }}</div>
            @endif
        </div>

        <!-- Course Information -->
        <x-card class="mb-6">
            <h3 class="text-xl font-bold text-warm-900 mb-6">課程資訊</h3>

            <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 科目內容 -->
                @if ($course->description_url)
                    <div>
                        <dt class="font-semibold text-warm-900 mb-2">科目內容</dt>
                        <dd class="text-warm-700">
                            <a href="{{ $course->description_url }}" target="_blank" rel="noopener" class="text-orange-600 hover:text-orange-700 underline underline-offset-4 inline-flex items-center gap-1">
                                檢視詳細內容
                                <x-heroicon-o-arrow-top-right-on-square class="size-4" />
                            </a>
                        </dd>
                    </div>
                @endif

                <!-- 必/選修 -->
                @if ($course->credit_type)
                    <div>
                        <dt class="font-semibold text-warm-900 mb-2">必/選修</dt>
                        <dd class="text-warm-700">{{ $course->credit_type }}</dd>
                    </div>
                @endif

                <!-- 學分 -->
                @if ($course->credits)
                    <div>
                        <dt class="font-semibold text-warm-900 mb-2">學分</dt>
                        <dd class="text-warm-700">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-1 text-orange-500" aria-hidden="true">
                                    @php
                                        $starCount = (int) floor($course->credits);
                                        $displayStars = min($starCount, 6);
                                    @endphp

                                    @for ($i = 0; $i < $displayStars; $i++)
                                        <x-heroicon-s-star class="size-4" />
                                    @endfor

                                    @if ($starCount > $displayStars)
                                        <span class="text-xs text-warm-600">+{{ $starCount - $displayStars }}</span>
                                    @endif
                                </div>

                                <div class="text-sm text-warm-600">{{ $course->credits }} 學分</div>
                            </div>
                        </dd>
                    </div>
                @endif

                <!-- 學系 -->
                @if ($course->department)
                    <div>
                        <dt class="font-semibold text-warm-900 mb-2">學系</dt>
                        <dd class="text-warm-700">{{ $course->department }}</dd>
                    </div>
                @endif

                <!-- 面授類別 -->
                @if ($course->in_person_class_type)
                    <div>
                        <dt class="font-semibold text-warm-900 mb-2">面授類別</dt>
                        <dd class="text-warm-700">{{ $course->in_person_class_type }}</dd>
                    </div>
                @endif

                <!-- 媒體 -->
                @if ($course->media)
                    <div>
                        <dt class="font-semibold text-warm-900 mb-2">媒體</dt>
                        <dd class="text-warm-700">{{ $course->media }}</dd>
                    </div>
                @endif

                <!-- 多媒體簡介 -->
                @if ($course->multimedia_url)
                    <div>
                        <dt class="font-semibold text-warm-900 mb-2">多媒體簡介</dt>
                        <dd class="text-warm-700">
                            <a href="{{ $course->multimedia_url }}" target="_blank" rel="noopener" class="text-orange-600 hover:text-orange-700 underline underline-offset-4 inline-flex items-center gap-1">
                                檢視簡介
                                <x-heroicon-o-arrow-top-right-on-square class="size-4" />
                            </a>
                        </dd>
                    </div>
                @endif

                <!-- 課程性質 -->
                @if ($course->nature)
                    <div>
                        <dt class="font-semibold text-warm-900 mb-2">課程性質</dt>
                        <dd class="text-warm-700">{{ $course->nature }}</dd>
                    </div>
                @endif
            </dl>
        </x-card>

        <!-- Course Classes -->
        @if ($course->classes->isNotEmpty())
            <x-card class="mb-6">
                <h3 class="text-xl font-bold text-warm-900 mb-6">視訊面授班級與上課時間</h3>

                @php
                    $typeOrder = ['morning', 'afternoon', 'evening', 'full_remote', 'micro_credit', 'computer_lab'];
                    $grouped = $course->classes->groupBy(fn($c) => $c->type->value);
                @endphp

                <div class="space-y-6">
                    @foreach ($typeOrder as $type)
                        @if (isset($grouped[$type]) && $grouped[$type]->isNotEmpty())
                            <div>
                                <div class="mb-3 font-semibold text-warm-900">{{ \App\Enums\CourseClassType::tryFrom($type)?->label() ?? $type }}</div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach ($grouped[$type] as $class)
                                        <div class="p-4 bg-warm-50 border-2 border-warm-200 rounded-lg">
                                            <div class="mb-3">
                                                <div class="flex items-start justify-between">
                                                    <div>
                                                        <div class="font-semibold text-warm-900">{{ $class->code }}</div>
                                                        @if ($class->teacher_name)
                                                            <div class="text-sm text-warm-700 mt-1 truncate">
                                                                @php
                                                                    $teacher = $class->teacher_name;
                                                                    $suffix = mb_substr($teacher, -2, null, 'UTF-8');
                                                                    $base = mb_substr($teacher, 0, mb_strlen($teacher, 'UTF-8') - 2, 'UTF-8');
                                                                @endphp
                                                                @if ($suffix === '老師')
                                                                    <span class="inline-flex items-baseline gap-0.5">
                                                                        <span>{{ $base }}</span>
                                                                        <span class="text-xs">老師</span>
                                                                    </span>
                                                                @else
                                                                    {{ $teacher }}
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="text-sm text-warm-600 whitespace-nowrap">
                                                        @if($class->start_time)
                                                            <div>{{ $class->start_time }} - {{ $class->end_time }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            @if ($class->schedules->isNotEmpty())
                                                <div class="mt-2 bg-white rounded p-3">
                                                    <p class="text-sm font-semibold text-warm-900 mb-2">視訊面授日期：</p>

                                                    {{-- 列出每一天；只有 schedule 本身有 start_time/end_time (override) 時，才在該日期旁顯示覆寫時間 --}}
                                                    <div class="text-sm text-warm-700 space-y-1">
                                                        @php
                                                            $schedulesByDate = $class->schedules->sortBy('date')->groupBy(function ($s) {
                                                                return $s->date->format('Y-m-d');
                                                            });
                                                        @endphp

                                                        @foreach ($schedulesByDate as $dateKey => $schedules)
                                                            @php
                                                                $s = $schedules->first();
                                                                $d = $s->date;
                                                                $weekday = ['日','一','二','三','四','五','六'][$d->dayOfWeek];
                                                            @endphp

                                                            <div class="flex items-center justify-between tabular-nums">
                                                                <div class="font-semibold">{{ $d->format('n/j') }} ({{ $weekday }})</div>

                                                                @if ($s->start_time || $s->end_time)
                                                                    <div class="text-sm text-warm-600 whitespace-nowrap">
                                                                        @if ($s->start_time && $s->end_time)
                                                                            {{ $s->start_time }} - {{ $s->end_time }}
                                                                        @elseif ($s->start_time)
                                                                            {{ $s->start_time }}
                                                                        @else
                                                                            {{ $s->end_time }}
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @else
                                                <p class="text-sm text-warm-600 mt-2">未設定上課時間</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </x-card>
        @endif

        <x-common-links />
    </div>
@endsection
