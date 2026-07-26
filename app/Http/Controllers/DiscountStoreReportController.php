<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DiscountStore;
use Coderflex\LaravelTurnstile\Rules\TurnstileCheck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use NouTools\Domains\DiscountStores\Actions\ReportDiscountStore;
use NouTools\Domains\DiscountStores\DataTransferObjects\ReportDiscountStoreDTO;

final class DiscountStoreReportController extends Controller
{
    public function store(
        Request $request,
        DiscountStore $store,
        ReportDiscountStore $reportDiscountStore,
    ): RedirectResponse {
        $validated = $request->validate([
            'is_valid' => ['required', 'boolean'],
            'comment' => ['nullable', 'string', 'max:500'],
            'cf-turnstile-response' => ['required', new TurnstileCheck],
        ]);

        $dto = new ReportDiscountStoreDTO(
            isValid: (bool) $validated['is_valid'],
            comment: ($validated['comment'] ?? '') !== '' ? $validated['comment'] : null,
        );

        $reportDiscountStore($store, $dto, $request);

        return redirect()
            ->route('discount-stores.show', $store)
            ->with('success', '已完成回報，感謝您的協助。');
    }
}
