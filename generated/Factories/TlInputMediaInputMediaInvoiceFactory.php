<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputMediaInputMediaInvoice (inputMediaInvoice). */
final class TlInputMediaInputMediaInvoiceFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaInvoice> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaInvoice::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'title' => 'title-2',
            'description' => 'description-3',
            'photo' => (string) new \Symfony\Component\Uid\UuidV7(),
            'invoice' => (string) new \Symfony\Component\Uid\UuidV7(),
            'payload' => 'Ynl0ZXMtNg==',
            'provider' => 'provider-7',
            'provider_data' => (string) new \Symfony\Component\Uid\UuidV7(),
            'start_param' => 'start_param-9',
            'extended_media' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
