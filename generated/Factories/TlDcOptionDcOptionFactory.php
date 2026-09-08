<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlDcOptionDcOption (dcOption). */
final class TlDcOptionDcOptionFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDcOptionDcOption> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDcOptionDcOption::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'ipv6' => true,
            'media_only' => true,
            'tcpo_only' => true,
            'cdn' => true,
            'static' => true,
            'this_port_only' => true,
            'tl_id' => 8,
            'ip_address' => 'ip_address-9',
            'port' => 10,
            'secret' => 'Ynl0ZXMtMTE=',
        ];
    }
}
