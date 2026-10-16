# {{ $viewModel->title }}

> 由空大同學提出修改、AI 協助編輯的知識庫。內容僅供參考，請以學校公告為準。撰寫指南：{{ $viewModel->writingGuideUrl }}｜語法說明：{{ $viewModel->syntaxGuideUrl }}

## 所有條目

@forelse ($viewModel->pages as $page)

- [{{ $page->title }}]({{ $page->url }}){{ $page->description ? '：'.$page->description : '' }}（Markdown 版本：{{ $page->url }}.md）
  @empty
  目前還沒有條目。
  @endforelse
