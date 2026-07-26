<?php

declare(strict_types=1);

namespace NouTools\Domains\DiscountStores\Actions;

use App\Enums\DiscountStoreStatus;
use App\Models\DiscountStore;
use App\Notifications\NewPendingDiscountStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use NouTools\Domains\DiscountStores\DataTransferObjects\SubmitDiscountStoreDTO;

final readonly class SubmitDiscountStore
{
    public function __invoke(SubmitDiscountStoreDTO $data, Request $request): DiscountStore
    {
        $store = new DiscountStore;
        $store->name = $data->name;
        $store->status = DiscountStoreStatus::Pending;
        $store->type = $data->type;
        $store->category_id = $data->categoryId;
        $store->city = $data->city;
        $store->district = $data->district;
        $store->address = $data->address;
        $store->verification_method = $data->verificationMethod;
        $store->discount_details = $data->discountDetails;
        $store->notes = $data->notes;
        $store->saveOrFail();

        Notification::route('discord-webhook', config('services.discord.webhooks.new_store'))
            ->notifyNow(new NewPendingDiscountStore($store));

        return $store;
    }
}
