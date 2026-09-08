<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMissingInviteeMissingInvitee (missingInvitee). */
final class TlMissingInviteeMissingInviteeFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMissingInviteeMissingInvitee> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMissingInviteeMissingInvitee::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'premium_would_allow_invite' => true,
            'premium_required_for_pm' => true,
            'user_id' => 1004,
        ];
    }
}
