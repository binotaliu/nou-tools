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
                    <h1 class="text-2xl font-bold text-warm-700 pb-0 mb-0!">
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

        <footer class="bg-warm-100 text-warm-700 py-6 mt-12">
            <div class="max-w-7xl mx-auto px-6 text-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4 print:hidden">
                <div class="w-full md:w-auto text-center md:text-left">
                    <p class="text-warm-700">&copy; {{ date('Y') }} NOU 小幫手 | <a href="mailto:nou-tools-contact@binota.org" class="hover:text-warm-900">聯絡本網站作者</a></p>
                </div>

                <div class="w-full md:w-2/3 text-center md:text-right">
                    <p class="mt-2 text-xs text-warm-500">免責聲明：本網站為學生自發製作之工具，僅供同學參考使用，並非學校官方發布；所有資訊以學校正式公告為準。</p>
                </div>
            </div>
        </footer>
    </body>
</html>
