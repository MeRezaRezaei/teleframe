<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlSendMessageActionSendMessageTextDraftAction (sendMessageTextDraftAction). */
final class TlSendMessageActionSendMessageTextDraftActionFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSendMessageActionSendMessageTextDraftAction> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSendMessageActionSendMessageTextDraftAction::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'random_id' => 1001,
            'text' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
