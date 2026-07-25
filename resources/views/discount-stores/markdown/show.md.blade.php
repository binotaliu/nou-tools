# {{ $store->name }}

- 分類：{{ $store->category->name }}
- 類型：{{ $store->type->label() }}
  @if ($store->city)
- 地區：{{ $store->city }}{{ $store->district }}
  @endif
  @if ($store->address)
- 地址：{{ $store->address }}
  @endif
- 優惠內容：{{ $store->discount_details }}
  @if ($store->verification_method)
- 驗證方式：{{ $store->verification_method }}
  @endif
  @if ($store->notes)
- 備註：{{ $store->notes }}
  @endif
  @if ($store->latestReport === null)
- 有效性：尚無回報
  @elseif ($store->latestReport->is_valid)
- 有效性：有效（{{ $store->latestReport->created_at->format('Y/m/d') }}）
  @else
- 有效性：此優惠似乎無法使用
  @endif
- 有效回報數：{{ $store->valid_reports_count }}
- 無效回報數：{{ $store->invalid_reports_count }}

## 最近回報

@forelse ($store->reports as $report)

- {{ $report->created_at->format('Y/m/d') }}：{{ $report->is_valid ? '有效' : '無效' }}
  @if ($report->comment)
  （{{ $report->comment }}）
  @endif
  @empty
  尚無回報。
  @endforelse

## 留言（{{ $store->comments_count }}）

@forelse ($store->comments as $comment)

- {{ $comment->nickname }}（{{ $comment->created_at->format('Y/m/d') }}）：{{ $comment->content }}
  @empty
  尚無留言。
  @endforelse

詳情頁面：{{ route('discount-stores.show', $store) }}
