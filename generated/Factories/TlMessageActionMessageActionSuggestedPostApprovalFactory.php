<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageActionMessageActionSuggestedPostApproval (messageActionSuggestedPostApproval). */
final class TlMessageActionMessageActionSuggestedPostApprovalFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionSuggestedPostApproval> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionSuggestedPostApproval::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'rejected' => true,
            'balance_too_low' => true,
            'reject_comment' => 'reject_comment-4',
            'schedule_date' => 5,
            'price' => 1006,
        ];
    }
}
