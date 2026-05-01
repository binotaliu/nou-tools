<?php

namespace App\Filament\Resources\DiscountStores\Pages;

use App\Filament\Resources\DiscountStores\DiscountStoreResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDiscountStore extends CreateRecord
{
    protected static string $resource = DiscountStoreResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (is_array($data['location'] ?? null)) {
            $data['latitude'] = isset($data['location']['lat']) ? (float) $data['location']['lat'] : null;
            $data['longitude'] = isset($data['location']['lng']) ? (float) $data['location']['lng'] : null;
        }

        unset($data['location']);

        return $data;
    }
}
