# 連結 / 學習指導中心目錄

> 彙整校內各處室、學系與學習指導中心的官方網站連結。

@foreach ($viewModel->linkGroups as $linkGroup)

## {{ $linkGroup->label }}

@foreach ($linkGroup->links as $link)

- [{{ $link->name }}]({{ $link->url }})
  @endforeach

@endforeach
@if ($viewModel->centerGroup)

## {{ $viewModel->centerGroup->label }}

@foreach ($viewModel->centerGroup->centers as $center)
@php
    $label = rawurlencode($center->name);
$osmUrl = "https://www.openstreetmap.org/?mlat={$center->latitude}&mlon={$center->longitude}&zoom=16&layers=M";
    $appleMapsUrl = "maps://maps.apple.com/?q={$label}&ll={$center->latitude},{$center->longitude}&z=16";
$googleMapsUrl = $center->googleMapsUrl ?? "https://maps.google.com/maps?q={$label}@{$center->latitude},{$center->longitude}&z=16";
@endphp

### {{ $center->name }}

- 地區：{{ $center->regionLabel }}
- 地址：{{ $center->address }}
- 電話：{{ $center->phone->toCollection()->map->display->join('、') }}
- 網站：{{ $center->url }}
  @if ($center->transportUrl)
- 交通資訊：{{ $center->transportUrl }}
  @endif
- 地圖：[OpenStreetMap]({{ $osmUrl }})、[Google 地圖]({{ $googleMapsUrl }})、[Apple 地圖]({{ $appleMapsUrl }})

@endforeach
@endif
