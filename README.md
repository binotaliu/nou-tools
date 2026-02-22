<h1 align="center">📖 NOU 小幫手・NOU Tools</h1>

NOU 小幫手 (NOU Tools) 是一款由 NOU 學生為同學打造的非官方小工具，旨在為 NOU 學生提供便利的功能，包含課表管理與學習進度追蹤等。

## 功能 / Features

- **課表管理**：自訂課表，可快速看到視訊面授時間，並支援 WebCal 訂閱。
- **學習進度追蹤**：記錄每週的課本與影音課程學習進度，並可加入個人備註。

## 開發 / Development

本專案採用 Laravel 框架開發，前端使用 Blade 與 TailwindCSS 搭配 Alpine.js 製作。  
若您有興趣參與貢獻，可 Clone 本專案後，依照以下步驟進行：

1. 請確保您的環境有 PHP 8.4 以上與 Node.js 22 以上。
2. 安裝相依套件：`composer install`
3. 執行 `composer run setup` —— 此指令會建立 `.env` 檔案、產生應用程式金鑰、執行資料庫遷移、安裝前端套件並打包前端資源。
4. 抓取課程資料：`php artisan course:fetch`、`php artisan course:fetch-map`、`php artisan exams:import`。
5. 啟動開發伺服器：`composer run dev`，預設會在 `http://localhost:8000` 提供服務。

開發時請遵守 [Code of Conduct](CODE_OF_CONDUCT.md) 中的行為準則，維持友善且包容的社群環境。

本專案含有測試，可執行 `php artisan test` 來確保功能正常。

## 授權 / License

本專案採用 `AGPL-3.0-or-later` 開放原始碼授權，詳細內容請參閱 [LICENSE](LICENSE) 檔案。

> [!NOTE]  
> 本專案使用了部分來自 NOU 官方網站的資料，這些資料的版權屬於原作者，請勿將本專案用於商業用途。
> 此外，由於我們採用 AGPL 授權，凡是使用到本專案程式碼的衍生作品也必須遵守相同的授權條款。換句話說，若您使用了本專案的程式碼，無論是修改還是直接使用，且包含通過網路提供服務，都必須將您的作品公開原始碼並採用 AGPL 授權。
