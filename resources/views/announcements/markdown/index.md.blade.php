# 學校公告

> 彙整校內公告，依發布時間排序。

@foreach ($viewModel->announcements as $announcement)

- **{{ $announcement->title }}**（{{ $announcement->source_name }} / {{ $announcement->category }}）@if ($announcement->expired_at?->isPast())（已過期）@endif
    - 發布時間：{{ $announcement->published_at?->format('Y/m/d') ?? '未提供' }}
    - 連結：{{ $announcement->url }}

@endforeach
