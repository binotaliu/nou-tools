<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="zh-Hant-TW">
    <title>{{ $title }}</title>
    <subtitle>整理空大各處室、學系與學習指導中心的公告，每兩週一期。</subtitle>
    <id>{{ route('newsletter.index') }}</id>
    <link
        rel="alternate"
        type="text/html"
        href="{{ route('newsletter.index') }}"
    />
    <link
        rel="self"
        type="application/atom+xml"
        href="{{ route('newsletter.feed') }}"
    />
    <updated
        >{{ \Illuminate\Support\Facades\Date::parse($issues->first()?->publishedAt ?? '2026-09-21')->toAtomString() }}</updated
    >
    <author>
        <name>NOU 小幫手</name>
    </author>
    @foreach ($issues as $issue)
        <entry>
            <title>{{ $issue->title }}</title>
            <id>{{ route('newsletter.show', $issue->issueKey) }}</id>
            <link
                rel="alternate"
                type="text/html"
                href="{{ route('newsletter.show', $issue->issueKey) }}"
            />
            <published
                >{{ \Illuminate\Support\Facades\Date::parse($issue->publishedAt ?? $issue->publishesOn)->toAtomString() }}</published
            >
            <updated
                >{{ \Illuminate\Support\Facades\Date::parse($issue->publishedAt ?? $issue->publishesOn)->toAtomString() }}</updated
            >
            <content
                type="html"
                >{{ view('newsletter.feed-entry', ['issue' => $issue])->render() }}</content
            >
        </entry>
    @endforeach
</feed>
