{{-- The way back to the web schedule (and its video classrooms). Sits in the
bottom-right corner of the sheet: in the empty calendar cell when the last row
of months leaves one, otherwise below the calendars. --}}
<div class="flex flex-col items-end gap-1 text-right">
    <div class="[&_svg]:h-full [&_svg]:w-full size-[24mm]">
        {!! $page->qrCodeSvg !!}
    </div>
    <p class="text-[9pt] font-bold">掃描 QR Code，開啟線上課表</p>
    <p class="text-[7.5pt] text-zinc-600">網頁內可檢視與編輯課表，並可進入視訊教室</p>
    <p class="max-w-[80mm] text-[6.5pt] break-all text-zinc-500">{{ $page->shareUrl }}</p>
</div>
