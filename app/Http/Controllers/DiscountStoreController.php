<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DiscountStoreStatus;
use App\Enums\DiscountStoreType;
use App\Models\DiscountStore;
use App\Models\DiscountStoreCategory;
use Coderflex\LaravelTurnstile\Rules\TurnstileCheck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use NouTools\Domains\DiscountStores\Actions\LoadTaiwanRegions;
use NouTools\Domains\DiscountStores\Actions\ShowDiscountStorePage;
use NouTools\Domains\DiscountStores\Actions\SubmitDiscountStore;
use NouTools\Domains\DiscountStores\DataTransferObjects\ShowDiscountStorePageData;
use NouTools\Domains\DiscountStores\DataTransferObjects\SubmitDiscountStoreDTO;

final class DiscountStoreController extends Controller
{
    public function index(
        ShowDiscountStorePage $showDiscountStorePage,
        ShowDiscountStorePageData $input,
    ): View {
        return view('discount-stores.index', [
            'viewModel' => $showDiscountStorePage($input),
        ]);
    }

    public function create(LoadTaiwanRegions $loadTaiwanRegions): View
    {
        $regions = $loadTaiwanRegions();

        $cities = collect($regions)
            ->pluck('name')
            ->values()
            ->all();

        $districtsByCity = collect($regions)
            ->mapWithKeys(fn (array $region): array => [
                $region['name'] => collect($region['districts'] ?? [])->pluck('name')->values()->all(),
            ])
            ->all();

        return view('discount-stores.create', [
            'categories' => DiscountStoreCategory::query()->orderBy('sort_order')->get(),
            'types' => DiscountStoreType::cases(),
            'cities' => $cities,
            'districtsByCity' => $districtsByCity,
        ]);
    }

    public function show(DiscountStore $store): View
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

        return view('discount-stores.show', [
            'store' => $store,
        ]);
    }

    public function store(Request $request, SubmitDiscountStore $submitDiscountStore): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:online,chain,local'],
            'category_id' => ['required', 'exists:discount_store_categories,id'],
            'city' => ['nullable', 'string', 'max:50'],
            'district' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'verification_method' => ['nullable', 'string', 'max:255'],
            'discount_details' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'cf-turnstile-response' => ['required', new TurnstileCheck],
        ]);

        $dto = new SubmitDiscountStoreDTO(
            name: $validated['name'],
            type: $validated['type'],
            categoryId: (int) $validated['category_id'],
            city: $validated['city'] !== '' ? $validated['city'] : null,
            district: $validated['district'] !== '' ? $validated['district'] : null,
            address: $validated['address'] ?? '',
            verificationMethod: $validated['verification_method'] ?? '',
            discountDetails: $validated['discount_details'],
            notes: ($validated['notes'] ?? '') !== '' ? $validated['notes'] : null,
        );

        $submitDiscountStore($dto, $request);

        return redirect()
            ->route('discount-stores.create')
            ->with('success', '已成功送出！您送出的店家資訊將在管理員審核後顯示在列表中。');
    }
}
