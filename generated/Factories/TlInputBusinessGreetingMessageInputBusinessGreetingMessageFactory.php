<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputBusinessGreetingMessageInputBusinessGreetingMessage (inputBusinessGreetingMessage). */
final class TlInputBusinessGreetingMessageInputBusinessGreetingMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBusinessGreetingMessageInputBusinessGreetingMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBusinessGreetingMessageInputBusinessGreetingMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'shortcut_id' => 1,
            'recipients' => 1002,
            'no_activity_days' => 3,
        ];
    }
}
