<?php

namespace App\Filament\Resources\DiscountStores\Pages;

use App\Filament\Resources\DiscountStores\DiscountStoreResource;
use App\Models\DiscountStore;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use NouTools\Domains\DiscountStores\Actions\GeoCodeStoreAddress;

class EditDiscountStore extends EditRecord
{
    protected static string $resource = DiscountStoreResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (filled($data['latitude'] ?? null) && filled($data['longitude'] ?? null)) {
            $data['location'] = [
                'lat' => (float) $data['latitude'],
                'lng' => (float) $data['longitude'],
            ];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (is_array($data['location'] ?? null)) {
            $data['latitude'] = isset($data['location']['lat']) ? (float) $data['location']['lat'] : null;
            $data['longitude'] = isset($data['location']['lng']) ? (float) $data['location']['lng'] : null;
        }

        unset($data['location']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getGeoCodeAction(),
            DeleteAction::make(),
        ];
    }

    private function getGeoCodeAction(): Action
    {
        return Action::make('geoCoder')
            ->label('使用 Nominatim 查詢座標')
            ->icon('heroicon-o-map-pin')
            ->color('info')
            ->action(function (DiscountStore $record): void {
                $coordinates = app(GeoCodeStoreAddress::class)($record);

                if ($coordinates['latitude'] === null || $coordinates['longitude'] === null) {
                    Notification::make()
                        ->warning()
                        ->title('無法自動查詢座標，請手動在地圖上選擇位置。')
                        ->send();

                    return;
                }

                $record->update([
                    'latitude' => $coordinates['latitude'],
                    'longitude' => $coordinates['longitude'],
                ]);

                $this->fillForm();
                Notification::make()
                    ->success()
                    ->title(sprintf(
                        '座標查詢成功！緯度: %s, 經度: %s',
                        round($coordinates['latitude'], 8),
                        round($coordinates['longitude'], 8)
                    ))
                    ->send();
            });
    }
}
