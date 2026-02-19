<!DOCTYPE html>
<html lang="zh-hant">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'NOU 小幫手')</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Alpine.js -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        @if (app()->environment('production'))
            <!-- Google Analytics -->
            <script async src="https://www.googletagmanager.com/gtag/js?id=G-1B65SQ4673"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());

                gtag('config', 'G-1B65SQ4673');
            </script>
        @endif
    </head>
    <body class="bg-warm-50 text-warm-900">
        <header class="bg-white border-b border-warm-200 sticky top-0 z-40 print:static">
            <div class="max-w-7xl mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-warm-700 pb-0 mb-0! inline-flex items-center gap-4">
                        <x-heroicon-o-book-open class="size-6 text-warm-700" />
                        <a href="{{ url('/') }}">NOU 小幫手</a>
                    </h1>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-6 py-8">
            @if (session('success'))
                <x-alert type="success">
                    {{ session('success') }}
                </x-alert>
            @endif

            @if ($errors->any())
                <x-alert type="error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            @yield('content')
        </main>

        <footer class="bg-warm-100 text-warm-900 py-8 mt-12 border-t border-warm-200 print:bg-white print:text-black">
            <div class="max-w-7xl mx-auto px-6">
                <div class="hidden print:block text-xs text-warm-800 text-center py-2">
                    <p class="mb-1">&copy; {{ date('Y') }} NOU 小幫手 — {{ url('/') }}<br>免責聲明：本網站為學生自發製作之工具，僅供參考，請以學校正式公告為準。</p>
                    <p class="text-xs">聯絡網站作者：nou-tools-contact@binota.org</p>
                </div>

                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 print:hidden">
                    <div class="flex items-center gap-4">
                        <div class="p-3">
                            <x-heroicon-o-book-open class="size-6 text-warm-700" />
                        </div>

                        <div>
                            <a href="{{ url('/') }}" class="text-lg font-semibold text-warm-700 hover:text-warm-900">NOU 小幫手</a>
                            <p class="text-xs text-warm-500 mt-1">給 NOU 同學的非官方小工具</p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-6 items-center">

                        <div class="text-sm text-warm-400 max-w-[40ch] text-center md:text-right">
                            <p class="text-xs text-left"><span class="font-semibold">免責聲明</span>：<br>本網站為學生自發製作之工具，僅供同學參考使用，並非學校官方發布；所有資訊以學校正式公告為準；本網站已盡可能提供準確資訊，但不保證其完整性或正確性；針對重要資訊，請使用者自行查證並以學校官方公告為準；使用本網站即表示同意此免責聲明之內容。</p>
                        </div>
                    </div>
                </div>

                <!-- Bottom row (screen only) -->
                <div class="mt-6 border-t border-warm-200 pt-4 text-xs text-warm-500 flex flex-col md:flex-row justify-between gap-3 items-center print:hidden">
                    <div>&copy; {{ date('Y') }} NOU 小幫手</div>
                    <div class="flex flex-col md:flex-row gap-y-2 gap-x-8 items-center">
                        <div class="text-xs">
                            <a href="mailto:nou-tools-contact@binota.org" class="inline-flex items-center gap-1 text-warm-500 hover:text-warm-600">
                                <x-heroicon-o-envelope class="size-3" />
                                聯絡作者
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </body>
</html>
