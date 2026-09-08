<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlWebViewResultWebViewResultUrl (webViewResultUrl). */
final class TlWebViewResultWebViewResultUrlFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebViewResultWebViewResultUrl> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebViewResultWebViewResultUrl::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'fullsize' => true,
            'fullscreen' => true,
            'same_origin' => true,
            'query_id' => 1005,
            'url' => 'url-6',
        ];
    }
}
