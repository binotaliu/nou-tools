<?php

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
use NouTools\Domains\DiscountStores\Actions\ReportDiscountStore;
use NouTools\Domains\DiscountStores\Actions\ShowDiscountStorePage;
use NouTools\Domains\DiscountStores\Actions\SubmitDiscountStore;
use NouTools\Domains\DiscountStores\Actions\SubmitStoreComment;
use NouTools\Domains\DiscountStores\DataTransferObjects\ReportDiscountStoreDTO;
use NouTools\Domains\DiscountStores\DataTransferObjects\ShowDiscountStorePageData;
use NouTools\Domains\DiscountStores\DataTransferObjects\SubmitDiscountStoreDTO;
use NouTools\Domains\DiscountStores\DataTransferObjects\SubmitStoreCommentDTO;

class DiscountStoreController extends Controller
{
    public function index(
        ShowDiscountStorePage $showDiscountStorePage,
        ShowDiscountStorePageData $input,
    ): View {
        $page = $showDiscountStorePage($input);

        return view('discount-stores.index', [
            'stores' => $page->stores,
            'categories' => $page->categories,
            'cities' => $page->cities,
            'selectedCategoryId' => $page->selectedCategoryId,
            'selectedType' => $page->selectedType,
            'search' => $page->search,
            'selectedCity' => $page->selectedCity,
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
            category_id: (int) $validated['category_id'],
            city: $validated['city'] !== '' ? $validated['city'] : null,
            district: $validated['district'] !== '' ? $validated['district'] : null,
            address: $validated['address'] ?? '',
            verification_method: $validated['verification_method'] ?? '',
            discount_details: $validated['discount_details'],
            notes: ($validated['notes'] ?? '') !== '' ? $validated['notes'] : null,
        );

        $submitDiscountStore($dto, $request);

        return redirect()
            ->route('discount-stores.create')
            ->with('success', '已成功送出！您送出的店家資訊將在管理員審核後顯示在列表中。');
    }

    public function storeReport(
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
            is_valid: (bool) $validated['is_valid'],
            comment: ($validated['comment'] ?? '') !== '' ? $validated['comment'] : null,
        );

        $reportDiscountStore($store, $dto, $request);

        return redirect()
            ->route('discount-stores.show', $store)
            ->with('success', '已完成回報，感謝您的協助。');
    }

    public function storeComment(
        Request $request,
        DiscountStore $store,
        SubmitStoreComment $submitStoreComment,
    ): RedirectResponse {
        $validated = $request->validate([
            'nickname' => ['required', 'string', 'max:100'],
            'content' => ['required', 'string', 'max:1000'],
            'cf-turnstile-response' => ['required', new TurnstileCheck],
        ]);

        $dto = new SubmitStoreCommentDTO(
            nickname: $validated['nickname'],
            content: $validated['content'],
        );

        $submitStoreComment($store, $dto, $request);

        return redirect()
            ->route('discount-stores.show', $store)
            ->with('success', '留言已送出，將在審核通過後顯示。');
    }
}
