<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlContactStatusContactStatus (contactStatus). */
final class TlContactStatusContactStatusFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactStatusContactStatus> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactStatusContactStatus::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => 1001,
            'status' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
