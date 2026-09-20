<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\DataTransferObjects;

use App\Enums\NewsletterReactionType;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

final class SetNewsletterReactionData extends Data
{
    /**
     * A null `reaction` withdraws the reader's reaction.
     */
    public function __construct(
        public ?NewsletterReactionType $reaction,
    ) {}

    public static function rules(): array
    {
        return [
            'reaction' => ['present', 'nullable', Rule::enum(NewsletterReactionType::class)],
        ];
    }

    public static function attributes(): array
    {
        return [
            'reaction' => '心情',
        ];
    }
}
