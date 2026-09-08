<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAuthSentCodeTypeSentCodeTypeFirebaseSms (auth.sentCodeTypeFirebaseSms). */
final class TlAuthSentCodeTypeSentCodeTypeFirebaseSmsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthSentCodeTypeSentCodeTypeFirebaseSms> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthSentCodeTypeSentCodeTypeFirebaseSms::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'nonce' => 'Ynl0ZXMtMg==',
            'play_integrity_project_id' => 1003,
            'play_integrity_nonce' => 'Ynl0ZXMtNA==',
            'receipt' => 'receipt-5',
            'push_timeout' => 6,
            'length' => 7,
        ];
    }
}
