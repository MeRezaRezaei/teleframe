<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionExportedInviteRevoke (channelAdminLogEventActionExportedInviteRevoke). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionExportedInviteRevokeFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionExportedInviteRevoke> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionExportedInviteRevoke::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'invite' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
