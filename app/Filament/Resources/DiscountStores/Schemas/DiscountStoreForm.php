<?php

namespace App\Filament\Resources\DiscountStores\Schemas;

use App\Enums\DiscountStoreStatus;
use App\Enums\DiscountStoreType;
use Dotswan\MapPicker\Fields\Map;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use NouTools\Domains\DiscountStores\Actions\LoadTaiwanRegions;

class DiscountStoreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基本資料')
                    ->schema([
                        TextInput::make('name')
                            ->label('名稱')
                            ->required()
                            ->maxLength(255),
                        Select::make('status')
                            ->label('狀態')
                            ->options(DiscountStoreStatus::class)
                            ->required()
                            ->default(DiscountStoreStatus::Pending),
                        Select::make('type')
                            ->label('類型')
                            ->options(DiscountStoreType::class)
                            ->required()
                            ->live(),
                        Select::make('category_id')
                            ->label('分類')
                            ->relationship('category', 'name')
                            ->required()
                            ->preload()
                            ->searchable(),
                    ])
                    ->columns(2),
                Section::make('地點資訊')
                    ->schema([
                        Select::make('city')
                            ->label('縣市')
                            ->options(function (): array {
                                $cities = collect(app(LoadTaiwanRegions::class)())
                                    ->pluck('name')
                                    ->values()
                                    ->all();

                                return array_combine($cities, $cities) ?: [];
                            })
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('district', null))
                            ->required(fn (Get $get): bool => $get('type') === DiscountStoreType::Local->value)
                            ->visible(fn (Get $get): bool => $get('type') !== DiscountStoreType::Online->value),
                        Select::make('district')
                            ->label('鄉鎮市區')
                            ->options(function (Get $get): array {
                                $city = $get('city');

                                if (blank($city)) {
                                    return [];
                                }

                                $region = collect(app(LoadTaiwanRegions::class)())
                                    ->firstWhere('name', $city);

                                if (! is_array($region)) {
                                    return [];
                                }

                                return collect($region['districts'] ?? [])
                                    ->pluck('name')
                                    ->values()
                                    ->mapWithKeys(fn (string $district): array => [$district => $district])
                                    ->all();
                            })
                            ->searchable()
                            ->required(fn (Get $get): bool => $get('type') === DiscountStoreType::Local->value)
                            ->visible(fn (Get $get): bool => $get('type') !== DiscountStoreType::Online->value),
                        TextInput::make('address')
                            ->label(fn (Get $get): string => $get('type') === DiscountStoreType::Online->value ? '網址' : '詳細地址')
                            ->maxLength(500)
                            ->default('')
                            ->dehydrateStateUsing(fn (mixed $state): string => $state ?? ''),
                        Hidden::make('latitude')
                            ->dehydrateStateUsing(fn (mixed $state): ?float => filled($state) ? (float) $state : null),
                        Hidden::make('longitude')
                            ->dehydrateStateUsing(fn (mixed $state): ?float => filled($state) ? (float) $state : null),
                        Map::make('location')
                            ->label('地圖座標選擇')
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => $get('type') !== DiscountStoreType::Online->value)
                            ->showMarker()
                            ->clickable(true)
                            ->draggable()
                            ->showZoomControl()
                            ->showFullscreenControl()
                            ->showMyLocationButton()
                            ->zoom(16)
                            ->tilesUrl(config('services.map.tileLayer'))
                            ->extraTileControl([
                                'attribution' => config('services.map.tileLayerAttribution'),
                            ])
                            ->extraStyles(['min-height: 24rem', 'border-radius: 0.75rem']),
                    ])
                    ->columns(2),
                Section::make('優惠資訊')
                    ->schema([
                        TextInput::make('verification_method')
                            ->label('驗證方式')
                            ->placeholder('例如：學生信箱、學生證、學生證+選課卡')
                            ->maxLength(255)
                            ->default('')
                            ->dehydrateStateUsing(fn (mixed $state): string => $state ?? ''),
                        Textarea::make('discount_details')
                            ->label('優惠內容')
                            ->required()
                            ->rows(3),
                        Textarea::make('notes')
                            ->label('備註')
                            ->rows(2),
                    ]),
            ]);
    }
}
