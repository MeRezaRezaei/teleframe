<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageEntityInputMessageEntityMentionName (inputMessageEntityMentionName). */
final class TlMessageEntityInputMessageEntityMentionNameFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageEntityInputMessageEntityMentionName> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageEntityInputMessageEntityMentionName::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_offset' => 1,
            'length' => 2,
            'user_id' => 1003,
        ];
    }
}
