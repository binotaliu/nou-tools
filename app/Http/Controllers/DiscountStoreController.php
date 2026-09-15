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
use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\DiscountStores\Actions\LoadTaiwanRegions;
use NouTools\Domains\DiscountStores\Actions\ShowDiscountStoreDetailPage;
use NouTools\Domains\DiscountStores\Actions\ShowDiscountStorePage;
use NouTools\Domains\DiscountStores\Actions\SubmitDiscountStore;
use NouTools\Domains\DiscountStores\DataTransferObjects\ShowDiscountStorePageData;
use NouTools\Domains\DiscountStores\DataTransferObjects\SubmitDiscountStoreDTO;
use NouTools\Domains\DiscountStores\ViewModels\DiscountStoreCategoryViewModel;
use Spatie\LaravelData\DataCollection;

final class DiscountStoreController extends Controller
{
    public function index(
        ShowDiscountStorePage $showDiscountStorePage,
        ShowDiscountStorePageData $input,
    ): Response {
        return Inertia::render('DiscountStores/Index', [
            'viewModel' => $showDiscountStorePage($input),
        ]);
    }

    public function create(LoadTaiwanRegions $loadTaiwanRegions): Response
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

        $categories = DiscountStoreCategory::query()->orderBy('sort_order')->get();

        return Inertia::render('DiscountStores/Create', [
            'categories' => DiscountStoreCategoryViewModel::collect(
                $categories->map(fn (DiscountStoreCategory $category) => DiscountStoreCategoryViewModel::fromModel($category)),
                DataCollection::class,
            ),
            'types' => collect(DiscountStoreType::cases())
                ->map(fn (DiscountStoreType $type): array => ['value' => $type->value, 'label' => $type->label()])
                ->all(),
            'cities' => $cities,
            'districtsByCity' => $districtsByCity,
            'turnstileSiteKey' => config('turnstile.turnstile_site_key'),
        ]);
    }

    public function show(DiscountStore $store, ShowDiscountStoreDetailPage $showDiscountStoreDetailPage): Response
    {
        abort_unless($store->status === DiscountStoreStatus::Online, 404);

        return Inertia::render('DiscountStores/Show', [
            'viewModel' => $showDiscountStoreDetailPage($store),
            'mapTileLayer' => config('services.map.tileLayer'),
            'mapTileLayerAttribution' => config('services.map.tileLayerAttribution'),
            'turnstileSiteKey' => config('turnstile.turnstile_site_key'),
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
            'tested_valid' => ['nullable', 'boolean'],
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
            testedValid: (bool) ($validated['tested_valid'] ?? false),
        );

        $submitDiscountStore($dto, $request);

        return redirect()
            ->route('discount-stores.submitted')
            ->with('submitted_store_name', $dto->name);
    }
}
