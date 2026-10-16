# {{ $revision->title }}

> {{ $wikiTitle }}｜分類：{{ $revision->categories === [] ? '未分類' : implode('、', $revision->categories) }}｜最後更新：{{ $revision->created_at->setTimezone('Asia/Taipei')->format('Y-m-d') }}｜編輯歷史：{{ route('wiki.history', $page->slug) }}

{!! $revision->body !!}

---

本條目由社群提出修改、AI 協助編輯，採用 [創用 CC 姓名標示－非商業性－相同方式分享 4.0 國際版授權條款 (CC BY-NC-SA 4.0)](https://creativecommons.org/licenses/by-nc-sa/4.0/deed.zh-hant) 釋出。內容僅供參考，請以學校公告為準。
