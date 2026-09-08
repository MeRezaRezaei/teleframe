<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlSendMessageActionInputSendMessageRichMessageDraftAction (inputSendMessageRichMessageDraftAction). */
final class TlSendMessageActionInputSendMessageRichMessageDraftActionFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSendMessageActionInputSendMessageRichMessageDraftAction> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSendMessageActionInputSendMessageRichMessageDraftAction::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'random_id' => 1001,
            'rich_message' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
