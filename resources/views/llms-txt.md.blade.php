# NOU 小幫手

> 給 NOU 同學的非官方小工具：管理個人課表與學習進度

NOU 小幫手是給國立空中大學（NOU）學生使用的非官方工具，協助建立與管理個人課表、追蹤學習進度、查看校園公告，以及尋找/回報學生優惠店家。

## Docs

- [首頁]({{ route('home') }}): 功能選單與快速入口
- [建立課表]({{ route('schedules.create') }}): 依課程建立個人課表
- [課程查詢]({{ route('course.schedule') }}): 依學期查詢課程開課時間表（Markdown 版本：[{{ route('course.schedule.md') }}]({{ route('course.schedule.md') }})）
- [校園公告]({{ route('announcements.index') }}): 最新校園公告列表（Markdown 版本：[{{ route('announcements.index.md') }}]({{ route('announcements.index.md') }})）
- [優惠店家列表]({{ route('discount-stores.index') }}): 學生優惠店家列表（Markdown 版本：[{{ route('discount-stores.index.md') }}]({{ route('discount-stores.index.md') }})）
- [連結 / 學習指導中心目錄]({{ route('directory.index') }}): 校內各處室、學系與學習指導中心的官方網站連結（Markdown 版本：[{{ route('directory.index.md') }}]({{ route('directory.index.md') }})）
- [優惠店家]({{ route('discount-stores.index') }}): 優惠店家資訊（Markdown 版本：[{{ route('discount-stores.index.md') }}]({{ route('discount-stores.index.md') }})）
- [Sitemap]({{ route('sitemap') }}): 完整網站地圖（XML）

## Optional

- 已建立的個人課表頁面（`/schedules/{uuid}`）皆提供對應的 Markdown 版本（於網址後加上 `.md`），但因含有個人資料，不會公開列出網址。
