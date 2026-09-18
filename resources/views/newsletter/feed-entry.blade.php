@if ($issue->highlightsIntro !== '' || $issue->highlightEvents->count() > 0)
    <h2>本期重點事項</h2>
    {!! $issue->highlightsIntro !!}
    @if ($issue->highlightEvents->count() > 0)
        <ul>
            @foreach ($issue->highlightEvents as $event)
                <li>
                    {{ $event->startDate === $event->endDate ? $event->startDate : $event->startDate.' ～ '.$event->endDate }}：{{ $event->name }}
                </li>
            @endforeach
        </ul>
    @endif
@endif
@foreach (['空大新消息' => $issue->newsItems, '各中心消息' => $issue->centerItems] as $sectionLabel => $items)
    @if ($items->count() > 0)
        <h2>{{ $sectionLabel }}</h2>
        @foreach ($items as $item)
            <h3>
                @if ($item->url)
                    <a href="{{ $item->url }}">{{ $item->headline }}</a>
                @else
                    {{ $item->headline }}
                @endif
            </h3>
            <p><small>{{ $item->sourceName }}</small></p>
            {!! $item->summary !!}
        @endforeach
    @endif
@endforeach
@foreach ($issue->columns as $column)
    <h2>{{ $column->title }}</h2>
    @if ($column->author)
        <p><small>{{ $column->author }}</small></p>
    @endif
    {!! $column->body !!}
@endforeach
