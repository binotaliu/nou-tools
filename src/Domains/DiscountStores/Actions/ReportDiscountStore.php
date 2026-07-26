<?php

declare(strict_types=1);

namespace NouTools\Domains\DiscountStores\Actions;

use App\Models\DiscountStore;
use App\Models\DiscountStoreReport;
use App\Notifications\NewDiscountStoreReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use NouTools\Domains\DiscountStores\DataTransferObjects\ReportDiscountStoreDTO;

final readonly class ReportDiscountStore
{
    public function __invoke(DiscountStore $store, ReportDiscountStoreDTO $data, Request $request): DiscountStoreReport
    {
        $report = new DiscountStoreReport;
        $report->store_id = $store->id;
        $report->is_valid = $data->isValid;
        $report->comment = $data->comment;
        $report->saveOrFail();

        Notification::route('discord-webhook', config('services.discord.webhooks.new_report'))
            ->notifyNow(new NewDiscountStoreReport($store, $report));

        return $report;
    }
}
