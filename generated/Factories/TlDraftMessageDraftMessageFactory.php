<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlDraftMessageDraftMessage (draftMessage). */
final class TlDraftMessageDraftMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDraftMessageDraftMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDraftMessageDraftMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'no_webpage' => true,
            'invert_media' => true,
            'reply_to' => (string) new \Symfony\Component\Uid\UuidV7(),
            'message' => 'message-5',
            'media' => (string) new \Symfony\Component\Uid\UuidV7(),
            'date' => 7,
            'effect' => 1008,
            'suggested_post' => (string) new \Symfony\Component\Uid\UuidV7(),
            'rich_message' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
