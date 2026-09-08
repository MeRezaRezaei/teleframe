<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlKeyboardButtonInputKeyboardButtonUrlAuth (inputKeyboardButtonUrlAuth). */
final class TlKeyboardButtonInputKeyboardButtonUrlAuthFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonInputKeyboardButtonUrlAuth> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonInputKeyboardButtonUrlAuth::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'request_write_access' => true,
            'style' => (string) new \Symfony\Component\Uid\UuidV7(),
            'text' => 'text-4',
            'fwd_text' => 'fwd_text-5',
            'url' => 'url-6',
            'bot' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
