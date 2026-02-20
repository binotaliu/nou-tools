# NOU 小幫手 (nou-tools)

給 NOU 學生的課表工具 — 幫助使用者搜尋課程、建立並設定個人課表，支援 iCal / webcal 訂閱、匯出 .ics、列印與 QR code 分享，以及顯示課程的面授日期與考試資訊。

---

## 主要功能

- 建立、編輯與分享個人課表（含 QR code）
- 課程搜尋、班級選擇與課表項目管理
- 匯出 / 訂閱 iCal（webcal）行事曆
- 顯示面授日期、覆寫上課時間與考試資訊
- 學校行事曆（學期重要日程）顯示

---

## 技術堆疊

- PHP 8.4+
- Laravel v12
- Tailwind CSS v4 + Vite
- Pest v4（測試）
- SQLite（開發/測試預設）

---

## 快速開始（本機）

1. 取得原始碼

   ```bash
   git clone <repo> && cd nou-tools
   ```

2. 安裝 PHP 依賴與前端套件（有一個一步驟腳本）

   ```bash
   composer run setup
   ```

   或分步驟：

   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   npm install
   npm run dev
   ```

3. 啟動開發伺服器
   - 使用內建開發腳本（會同時啟動 server、queue、logs 與 Vite）

     ```bash
     composer dev
     ```

   - 或單獨啟動：

     ```bash
     php artisan serve
     npm run dev
     ```

   - 使用 Laravel Herd 時，網站通常可在 `https://nou-tools.test`（或專案目錄 kebab-case）存取。

---

## 常用指令

- 本機開發（所有服務）：`composer dev`
- 建置資產：`npm run build`
- 格式化前端檔案：`npm run format`
- 格式化 PHP：`vendor/bin/pint` 或 `npm run format:php`
- 執行測試：`composer test` 或 `php artisan test --compact`

---

## 環境設定（重要）

- 複製 `.env.example` 並設定資料庫與其他服務。
- 用於顯示學期的設定（預設放在 `.env` / `config/app.php`）：

  ```env
  CURRENT_SEMESTER=2025B
  CURRENT_SEMESTER_START=2026-02-23
  CURRENT_SEMESTER_END=2026-07-05
  ```

> 注意：學校行事曆事件定義在 `config/school-schedules.php`，可依學期更新。

---

## 主要路由（摘錄）

- GET / — 首頁（功能選單、今日面授）
- GET /schedules/create — 建立課表
- POST /schedules — 儲存課表
- GET /schedules/{schedule} — 檢視 / 分享課表（含 QR / 下載 .ics / webcal）
- GET /schedules/{schedule}/edit — 編輯已存課表
- GET /schedules/{schedule}/calendar — 下載 / 訂閱 iCal
- GET /courses/{course} — 檢視單一課程與班級資訊

(完整路由請參考 `routes/web.php`)

---

## 資料模型 & 目錄概覽

- 主要 Eloquent 模型：`Course`, `CourseClass`, `ClassSchedule`, `StudentSchedule`, `StudentScheduleItem`, `User`
- 重要目錄：
  - `app/Services/` — 資料解析與排程邏輯（例如 NOU 解析器、考試/學期服務）
  - `resources/views/` — Blade 視圖（編輯、檢視、首頁）
  - `tests/` — Pest 測試

---

## 測試與 CI

- 使用 Pest（PHP）執行測試：`composer test`
- 測試環境使用 SQLite（記憶體或 `database/database.sqlite`）— 相關設定見 `phpunit.xml`。

---

## 開發規範

- 代碼格式化：前端使用 Prettier、Blade plugin；PHP 使用 Pint。
- 提交前會透過 Husky + lint-staged 自動執行格式化。
- 新增功能請附上 Pest 測試（feature/unit）並確保 `composer test` 全綠。

---

## 想法 / TODO（快速導覽）

- 支援更多學期資料來源匯入
- UI/UX 優化（行事曆視覺化）
- 使用者帳號與雲端同步（目前以「擁有連結即可編輯」為分享機制）

---

## 貢獻

歡迎開 PR。請先執行：

```bash
composer install
npm install
npm run format
vendor/bin/pint
composer test
```

---

## 授權

AGPL-3.0-or-later
