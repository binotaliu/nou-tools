<?php

declare(strict_types=1);

namespace NouTools\Domains\DiscountStores\ViewModels;

use App\Models\DiscountStore;
use Illuminate\Support\Str;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * A discount store detail page (優惠店家詳情), including its recent reports and
 * approved comments.
 */
final class DiscountStoreDetailViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $typeValue,
        public string $typeLabel,
        public ?string $categoryName,
        public ?string $categoryIcon,
        public ?string $city,
        public ?string $district,
        public ?string $address,
        public string $discountDetails,
        public ?string $verificationMethod,
        public ?string $notes,
        public ?string $expiresAtDateTime,
        public ?float $latitude,
        public ?float $longitude,
        #[DataCollectionOf(DiscountStoreReportViewModel::class)]
        public DataCollection $reports,
        public int $validReportsCount,
        public int $invalidReportsCount,
        #[DataCollectionOf(DiscountStoreCommentViewModel::class)]
        public DataCollection $comments,
        public int $commentsCount,
        public string $seoDate,
        public string $seoDescription,
    ) {}

    public static function fromModel(DiscountStore $store): self
    {
        $seoDate = $store->updated_at->format('Y/m/d');
        $seoDiscountSummary = Str::of($store->discount_details)->squish()->limit(40);

        return new self(
            id: $store->id,
            name: $store->name,
            typeValue: $store->type->value,
            typeLabel: $store->type->label(),
            categoryName: $store->category?->name,
            categoryIcon: $store->category?->icon,
            city: $store->city,
            district: $store->district,
            address: $store->address,
            discountDetails: $store->discount_details,
            verificationMethod: $store->verification_method,
            notes: $store->notes,
            expiresAtDateTime: $store->expires_at?->timezone('Asia/Taipei')->format('Y/m/d H:i'),
            latitude: $store->latitude !== null ? (float) $store->latitude : null,
            longitude: $store->longitude !== null ? (float) $store->longitude : null,
            reports: DiscountStoreReportViewModel::collect(
                $store->reports->map(fn ($report) => DiscountStoreReportViewModel::fromModel($report)),
                DataCollection::class,
            ),
            validReportsCount: (int) $store->valid_reports_count,
            invalidReportsCount: (int) $store->invalid_reports_count,
            comments: DiscountStoreCommentViewModel::collect(
                $store->comments->map(fn ($comment) => DiscountStoreCommentViewModel::fromModel($comment)),
                DataCollection::class,
            ),
            commentsCount: (int) $store->comments_count,
            seoDate: $seoDate,
            seoDescription: "國立空中大學學生是否可享有 {$store->name} 優惠？{$seoDate} 空大學生可享有 {$seoDiscountSummary}。更多優惠請看 NOU 小幫手。",
        );
    }
}
