# 貢獻指南 / Contributing

感謝你對 NOU 小工具的興趣與貢獻！為了讓協作順暢，請在提出 Issue 或 Pull Request 前遵守下列準則。

## 1. 行為守則

請遵守本專案的行為守則：[CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## 2. 提交議題（Issues）

- 在提出 Issue 前，先搜尋現有議題以避免重複。
- 清楚描述你遇到的問題或建議，附上重現步驟、相關日誌與環境資訊（PHP、Laravel、Node 版本等）。

## 3. Pull Request 指南

- fork 並從 `main` 建立功能分支（例如 `feature/add-thing` 或 `fix/bug-xyz`）。
- 每個 PR 應專注於單一變更主題。
- 在 PR 描述中說明變更目的、實作要點與任何相依事項。

若你在 PR 中新增或修改行為，請同時新增或更新相對應的測試。

## 4. 本機開發與測試

建議先依照 README 的快速開法設定本機環境。常用指令：

```bash
composer install
composer setup
```

格式化與測試：

```bash
npm run format
./vendor/bin/pint
composer test
```

## 5. 程式風格與檢查

- PHP：請遵循專案的 Pint 設定，提交前執行 `vendor/bin/pint`。
- JavaScript/CSS/Blade：請使用專案設定的格式化工具（`npm run format`）。

## 6. 安全性回報

若發現安全性或機敏資訊洩漏，請不要在公開 Issue 回報。使用專案 README 或 maintainer 指定的聯絡方式私下通知。

## 7. 授權

本專案使用 AGPL-3.0-or-later，詳細請參閱專案根目錄的 LICENSE。

---

再次感謝你的貢獻！如果你需要協助，歡迎在 Issue 中詢問或在 PR 中標註 Maintainer。
