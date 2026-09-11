# 自習室

## 公告

{{ $announcementMarkdown }}

## 開放時間

{{ $viewModel->openHoursLabel }}

## 目前在線人數

{{ $viewModel->roomState->totals->occupantCount }} 人

## 連結

- [自習室（網頁版）]({{ route('study-room.show') }})：即時座位圖與番茄鐘計時器僅在網頁版提供。
