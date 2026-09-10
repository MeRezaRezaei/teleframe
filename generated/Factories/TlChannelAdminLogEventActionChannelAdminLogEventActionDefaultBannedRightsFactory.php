<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionDefaultBannedRights (channelAdminLogEventActionDefaultBannedRights). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionDefaultBannedRightsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionDefaultBannedRights> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionDefaultBannedRights::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'prev_banned_rights' => 1001,
            'new_banned_rights' => 1002,
        ];
    }
}
