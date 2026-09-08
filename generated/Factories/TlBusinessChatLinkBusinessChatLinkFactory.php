<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBusinessChatLinkBusinessChatLink (businessChatLink). */
final class TlBusinessChatLinkBusinessChatLinkFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessChatLinkBusinessChatLink> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessChatLinkBusinessChatLink::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'link' => 'link-2',
            'message' => 'message-3',
            'title' => 'title-4',
            'views' => 5,
        ];
    }
}
