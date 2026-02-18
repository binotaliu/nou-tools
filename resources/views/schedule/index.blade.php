@extends('layouts.app')

@section('title', '首頁 - 課表編輯')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Hero Section -->
        <div class="flex flex-col justify-center">
            <h2 class="text-4xl font-bold text-warm-900 mb-4">
                自主編排您的課程表
            </h2>
            <p class="text-lg text-warm-700 mb-6 leading-relaxed">
                輕鬆搜尋您感興趣的課程，選擇您想上的班級，系統將自動為您保存課表。
                無需登入，使用簡單方便！
            </p>
            <div class="flex gap-4">
                <a href="{{ route('schedule.create') }}" class="btn bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-lg px-8 py-3">
                    開始編排課表
                </a>
                <a href="#features" class="btn bg-white border-2 border-warm-300 text-warm-900 font-semibold rounded-lg px-8 py-3 hover:bg-warm-50">
                    了解更多
                </a>
            </div>
        </div>

        <!-- Feature Cards -->
        <div class="grid grid-cols-1 gap-4">
            <div class="bg-white p-6 rounded-lg border-l-4 border-orange-500 shadow-sm">
                <h3 class="text-xl font-bold text-warm-900 mb-2">🔍 智慧搜尋</h3>
                <p class="text-warm-700">
                    快速搜尋所有課程，找到您感興趣的課程。
                </p>
            </div>
            <div class="bg-white p-6 rounded-lg border-l-4 border-amber-500 shadow-sm">
                <h3 class="text-xl font-bold text-warm-900 mb-2">📋 靈活編排</h3>
                <p class="text-warm-700">
                    選擇您想上的班級，系統自動整理您的課程表。
                </p>
            </div>
            <div class="bg-white p-6 rounded-lg border-l-4 border-yellow-500 shadow-sm">
                <h3 class="text-xl font-bold text-warm-900 mb-2">📱 行事曆同步</h3>
                <p class="text-warm-700">
                    一鍵將課表匯入 Google Calendar 或 Apple Calendar。
                </p>
            </div>
        </div>
    </div>

    <div id="features" class="mt-16 pt-8 border-t border-warm-200">
        <h2 class="text-3xl font-bold text-warm-900 mb-8">使用步驟</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="bg-orange-100 text-orange-700 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-4 text-xl font-bold">1</div>
                <h3 class="font-bold mb-2 text-warm-900">搜尋課程</h3>
                <p class="text-warm-700">在搜尋欄輸入課程名稱，找到您想上的課程。</p>
            </div>
            <div class="text-center">
                <div class="bg-amber-100 text-amber-700 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-4 text-xl font-bold">2</div>
                <h3 class="font-bold mb-2 text-warm-900">選擇班級</h3>
                <p class="text-warm-700">點擊課程，選擇您想上的班級和時段。</p>
            </div>
            <div class="text-center">
                <div class="bg-yellow-100 text-yellow-700 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-4 text-xl font-bold">3</div>
                <h3 class="font-bold mb-2 text-warm-900">保存課表</h3>
                <p class="text-warm-700">完成編排後送出，系統自動保存您的課表。</p>
            </div>
        </div>
    </div>
@endsection
