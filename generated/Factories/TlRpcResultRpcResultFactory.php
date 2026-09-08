<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlRpcResultRpcResult (rpc_result). */
final class TlRpcResultRpcResultFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRpcResultRpcResult> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRpcResultRpcResult::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'req_msg_id' => 1001,
            'result' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
