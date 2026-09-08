<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionExportedInviteEdit (channelAdminLogEventActionExportedInviteEdit). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionExportedInviteEditFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionExportedInviteEdit> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionExportedInviteEdit::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'prev_invite' => (string) new \Symfony\Component\Uid\UuidV7(),
            'new_invite' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
