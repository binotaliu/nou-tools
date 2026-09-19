# {{ $viewModel->title }}

> 整理空大各處室、學系與學習指導中心的公告，每兩週一期，於週一發刊。期號為發刊週一的 ISO 週次（例如 2026-W39）。

- Atom 訂閱：{{ $viewModel->feedUrl }}

@if ($viewModel->latestIssue)

## 最新一期

- [{{ $viewModel->latestIssue->title }}]({{ $viewModel->latestIssue->url }})（{{ $viewModel->latestIssue->publishesOn }} 發刊，涵蓋 {{ $viewModel->latestIssue->highlightsFrom }} 至 {{ $viewModel->latestIssue->highlightsTo }}；Markdown 版本：{{ $viewModel->latestIssue->url }}.md）
  @endif

## 過往期刊

@forelse ($viewModel->issues as $issue)

- [{{ $issue->title }}]({{ $issue->url }})（{{ $issue->publishesOn }} 發刊，涵蓋 {{ $issue->highlightsFrom }} 至 {{ $issue->highlightsTo }}；Markdown 版本：{{ $issue->url }}.md）
  @empty
  目前還沒有已發布的期數。
  @endforelse

@if ($viewModel->issues->lastPage() > 1)
---

第 {{ $viewModel->issues->currentPage() }} / {{ $viewModel->issues->lastPage() }} 頁
@if ($viewModel->issues->previousPageUrl())

- 上一頁：{{ $viewModel->issues->previousPageUrl() }}
  @endif
  @if ($viewModel->issues->nextPageUrl())
- 下一頁：{{ $viewModel->issues->nextPageUrl() }}
  @endif
  @endif
