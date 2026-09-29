# 學校行事曆

> {{ $viewModel->termLabel }}（{{ $viewModel->term }}）的完整行事曆，日期為臺北時間。可用 `?term=2026A` 查詢其他學期。

{{ collect($viewModel->events)->map(fn (array $event): string => '- '.$event['start'].($event['end'] !== $event['start'] ? ' ～ '.$event['end'] : '').'：'.$event['name'])->whenEmpty(fn ($lines) => $lines->push('本學期尚無行事曆資料。'))->implode("\n") }}
