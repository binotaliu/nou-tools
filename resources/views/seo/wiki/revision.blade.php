{{-- Edit forms and old versions shouldn't compete with the page itself in search results. --}}
@include('seo._meta', [
    'title' => '知識庫 - NOU 小幫手',
    'noindex' => true,
])
