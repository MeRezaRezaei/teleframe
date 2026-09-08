<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlRestrictionReasonRestrictionReason (restrictionReason). */
final class TlRestrictionReasonRestrictionReasonFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRestrictionReasonRestrictionReason> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRestrictionReasonRestrictionReason::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'platform' => 'platform-1',
            'reason' => 'reason-2',
            'text' => 'text-3',
        ];
    }
}
