# {{ $issue->displayTitle() }}

> {{ $issue->publishes_on->toDateString() }} 發刊｜本期重點涵蓋 {{ $issue->highlights_from->toDateString() }} 至 {{ $issue->highlights_to->toDateString() }}@unless ($issue->isPublished())｜{{ $issue->status->label() }}（預覽）@endunless

## 本期重點事項

{!! $issue->highlights_intro !!}

@foreach ($issue->highlights_events ?? [] as $event)

- {{ $event['start'] === $event['end'] ? $event['start'] : $event['start'].' ～ '.$event['end'] }}：{{ $event['name'] }}
  @endforeach

@if ($newsItems->isNotEmpty())

## 空大新消息

@foreach ($newsItems as $item)

### {{ $item->headline }}

{{ $item->source_name }}@if ($item->url)｜原文：{{ $item->url }}@endif

{!! $item->summary !!}

@endforeach
@endif
@if ($centerItemsBySource->isNotEmpty())

## 各中心消息

@foreach ($centerItemsBySource as $sourceName => $items)

### {{ $sourceName }}

@foreach ($items as $item)

- **{{ $item->headline }}**@if (filled($item->summary))：{!! $item->summary !!}@endif @if ($item->url)（原文：{{ $item->url }}）@endif

@endforeach
@endforeach
@endif
@foreach ($issue->columns as $column)

## {{ $column->title }}

@if ($column->author)

> {{ $column->author }}

@endif
{!! $column->body !!}

@endforeach
