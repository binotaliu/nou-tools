# 學校公告

> 彙整校內公告，依發布時間排序。

## 公告列表

@foreach ($viewModel->announcements as $announcement)

- **{{ $announcement->title }}**（{{ $announcement->source_name }} / {{ $announcement->category }}）@if ($announcement->expired_at?->isPast())（已過期）@endif
    - 發布時間：{{ $announcement->published_at?->format('Y/m/d') ?? '未提供' }}
    - 連結：{{ $announcement->url }}

@endforeach

## 篩選

可透過 `source_categories[來源][]=分類` 查詢參數篩選特定來源的分類（可重複指定多組來源與分類）。

@if ($viewModel->selectedSources !== [])
目前篩選來源：{{ implode('、', $viewModel->selectedSources) }}
@else
目前未篩選來源，顯示全部來源。
@endif

@foreach ($viewModel->sourceCategorySelections as $selection)
@continue ($selection->selectedCategories === [] && $selection->availableCategories === [])

- **{{ $selection->source }}** - 可用分類：{{ $selection->availableCategories !== [] ? implode('、', $selection->availableCategories) : '無' }} - 已篩選分類：{{ $selection->selectedCategories !== [] ? implode('、', $selection->selectedCategories) : '全部' }}
  @endforeach

---

第 {{ $viewModel->announcements->currentPage() }} / {{ $viewModel->announcements->lastPage() }} 頁（共 {{ $viewModel->announcements->total() }} 筆）

@if ($viewModel->announcements->previousPageUrl())

- 上一頁：{{ $viewModel->announcements->previousPageUrl() }}
  @endif
  @if ($viewModel->announcements->nextPageUrl())
- 下一頁：{{ $viewModel->announcements->nextPageUrl() }}
  @endif
