# 優惠店家

> 學生優惠店家列表，歡迎回報或新增店家資訊。此區資料由 112姍姍 同學維護。

@foreach ($stores as $store)

## {{ $store->name }}

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
  @if ($store->latestReport === null)
- 有效性：尚無回報
  @elseif ($store->latestReport->is_valid)
- 有效性：有效（{{ $store->latestReport->created_at->format('Y/m/d') }}）
  @else
- 有效性：此優惠似乎無法使用
  @endif
- 詳情頁面：{{ route('discount-stores.show', $store) }}（Markdown 版本：{{ route('discount-stores.show.md', $store) }}）

@endforeach
