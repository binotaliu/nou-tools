> 分類：{{ $article->type->label() }}｜作者：{{ $article->author }}｜發表於：{{ $article->publishedAt->format('Y-m-d') }}@if ($article->updatedAt)｜更新於：{{ $article->updatedAt->format('Y-m-d') }}@endif

{!! $rawContent !!}

---

本文採用 [創用 CC 姓名標示－非商業性－相同方式分享 4.0 國際版授權條款 (CC BY-NC-SA 4.0)](https://creativecommons.org/licenses/by-nc-sa/4.0/deed.zh-hant) 釋出。
