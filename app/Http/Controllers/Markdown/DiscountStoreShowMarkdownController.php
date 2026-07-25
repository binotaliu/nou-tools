<?php

namespace App\Http\Controllers\Markdown;

use App\Enums\DiscountStoreStatus;
use App\Http\Controllers\Controller;
use App\Models\DiscountStore;
use Illuminate\Http\Response;

class DiscountStoreShowMarkdownController extends Controller
{
    public function __invoke(DiscountStore $store): Response
    {
        abort_unless($store->status === DiscountStoreStatus::Online, 404);

        $store->load([
            'category',
            'reports' => fn ($query) => $query->latest()->limit(6),
            'comments' => fn ($query) => $query->where('is_approved', true)->latest(),
        ])->loadCount([
            'reports as valid_reports_count' => fn ($query) => $query->where('is_valid', true),
            'reports as invalid_reports_count' => fn ($query) => $query->where('is_valid', false),
            'comments' => fn ($query) => $query->where('is_approved', true),
        ]);

        return response()
            ->view('discount-stores.markdown.show', ['store' => $store])
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }
}
