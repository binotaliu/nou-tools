@extends('layouts.lite')

@php
    $scheduleName = $viewModel->name ?: '我的課表';
    $generatedAt = now('Asia/Taipei');
@endphp

@section('title', $scheduleName.'（備份）')

@section('content')
    {{-- Shown when /sw.js served this copy in place of a page it couldn't get (it sets data-emergency on <html>). --}}
    <div id="lite-banner" class="banner" role="status" hidden>
        目前無法連線到 NOU
        小幫手（網路或伺服器發生問題），這是上次開啟時備份的課表。視訊上課連結仍可使用。
    </div>

    <h2>{{ $scheduleName }}</h2>
    <p class="meta">
        {{ \Illuminate\Support\Str::toSemesterDisplay($viewModel->selectedTerm) }} ·
        備份時間
        <time
            datetime="{{ $generatedAt->toIso8601String() }}"
            >{{ $generatedAt->format('n/j H:i') }}</time
        >
    </p>

    @forelse ($viewModel->items as $item)
        @php
            $class = $item->courseClass;
        @endphp

        <section class="card">
            <h3>{{ $item->courseName }}</h3>

            @if ($class === null || $class->isTentative)
                <p class="meta">尚未選定班級</p>
            @else
                <p class="meta">
                    {{ $class->code }}
                    @if ($class->teacherName)
                        · {{ $class->teacherName }}
                    @endif
                    @if ($class->startTime)
                        · {{ $class->startTime }} - {{ $class->endTime }}
                    @endif
                </p>

                @if ($class->link || $class->backupClassroomUrl)
                    <div class="buttons">
                        @if ($class->link)
                            <a
                                class="button primary"
                                href="{{ $class->link }}"
                                target="_blank"
                                rel="noopener"
                                >進入教室</a
                            >
                        @endif
                        @if ($class->backupClassroomUrl)
                            <a
                                class="button"
                                href="{{ $class->backupClassroomUrl }}"
                                target="_blank"
                                rel="noopener"
                                >備用教室</a
                            >
                        @endif
                    </div>
                @endif

                @if ($class->schedules->count() > 0)
                    <p class="next meta" hidden></p>

                    <details>
                        <summary>
                            全部上課日期（{{ $class->schedules->count() }}）
                        </summary>
                        <ul class="dates">
                            @foreach ($class->schedules->toCollection()->sortBy(fn ($s) => $s->date) as $classSchedule)
                                @php
                                    $start = $classSchedule->startTime ?? $class->startTime;
                                    $end = $classSchedule->endTime ?? $class->endTime;
                                @endphp
                                <li
                                    data-date="{{ $classSchedule->date->format('Y-m-d') }}"
                                    data-start="{{ $start }}"
                                    data-end="{{ $end }}"
                                >
                                    {{ $classSchedule->date->isoFormat('M/D (dd)') }}
                                    @if ($start)
                                        {{ $start }} - {{ $end }}
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            @endif
        </section>
    @empty
        <section class="card">
            <p>這份課表還沒有加入任何課程。</p>
        </section>
    @endforelse

    <div class="buttons">
        <a class="button" href="{{ route('schedules.show', $viewModel->uuid) }}"
            >開啟完整課表</a
        >
    </div>
@endsection

@push('scripts')
    <script @cspNonce>
        if (document.documentElement.hasAttribute('data-emergency')) {
            document.getElementById('lite-banner').hidden = false
        }

        // Class times are published in Taipei time (UTC+8, no DST). This page
        // is a cached copy, so everything that depends on "now" or on the
        // viewer's zone is worked out here: which dates are past, the next
        // class, and a 你的時間 line (same wording as the schedule page)
        // when the viewer isn't on UTC+8.
        ;(() => {
            const WEEKDAYS = ['日', '一', '二', '三', '四', '五', '六']
            const now = Date.now()

            const instant = (ymd, hm) =>
                new Date(`${ymd}T${hm || '00:00'}:00+08:00`)

            const pad = n => String(n).padStart(2, '0')
            const ymdOf = d =>
                `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
            const hmOf = d => `${pad(d.getHours())}:${pad(d.getMinutes())}`
            const dayOf = d =>
                `${pad(d.getMonth() + 1)}/${pad(d.getDate())} (${WEEKDAYS[d.getDay()]})`

            function gmtLabel(d) {
                const offset = -d.getTimezoneOffset()
                const sign = offset < 0 ? '-' : '+'
                const hours = Math.floor(Math.abs(offset) / 60)
                const minutes = Math.abs(offset) % 60

                return `GMT${sign}${hours}${minutes ? ':' + pad(minutes) : ''}`
            }

            // null when the viewer is on UTC+8 or the class has no time.
            function localHint(ymd, start, end) {
                if (!start) {
                    return null
                }

                const from = instant(ymd, start)
                const to = instant(ymd, end || start)

                if (-from.getTimezoneOffset() === 480) {
                    return null
                }

                const datePrefix = ymdOf(from) === ymd ? '' : `${dayOf(from)} `

                return `你的時間 · ${datePrefix}${hmOf(from)} ~ ${hmOf(to)} (${gmtLabel(from)})`
            }

            document.querySelectorAll('section.card').forEach(card => {
                const upcoming = []

                card.querySelectorAll('li[data-date]').forEach(li => {
                    const { date, start, end } = li.dataset
                    // Without a time, a date counts until the Taipei day ends.
                    const endsAt = end
                        ? instant(date, end)
                        : instant(date, '23:59')

                    if (endsAt.getTime() < now) {
                        li.classList.add('past')
                    } else {
                        upcoming.push(li)
                    }

                    const hint = localHint(date, start, end)

                    if (hint) {
                        const line = document.createElement('div')

                        line.className = 'local meta'
                        line.textContent = hint
                        li.appendChild(line)
                        li.dataset.hint = hint
                    }
                })

                const next = card.querySelector('.next')

                if (!next) {
                    return
                }

                if (upcoming.length === 0) {
                    next.textContent = '本學期課程已結束'
                } else {
                    const first = upcoming[0]
                    const label = first.firstChild.textContent
                        .trim()
                        .replace(/\s+/g, ' ')

                    next.textContent = `下一堂：${label}`

                    if (first.dataset.hint) {
                        next.appendChild(document.createElement('br'))
                        next.appendChild(
                            document.createTextNode(first.dataset.hint)
                        )
                    }
                }

                next.hidden = false
            })
        })()
    </script>
@endpush
