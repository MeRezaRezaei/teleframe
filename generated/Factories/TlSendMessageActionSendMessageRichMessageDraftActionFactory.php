<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlSendMessageActionSendMessageRichMessageDraftAction (sendMessageRichMessageDraftAction). */
final class TlSendMessageActionSendMessageRichMessageDraftActionFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSendMessageActionSendMessageRichMessageDraftAction> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSendMessageActionSendMessageRichMessageDraftAction::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'random_id' => 1001,
            'rich_message' => 1002,
        ];
    }
}
