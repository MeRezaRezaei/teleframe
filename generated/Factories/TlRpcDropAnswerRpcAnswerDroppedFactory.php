<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlRpcDropAnswerRpcAnswerDropped (rpc_answer_dropped). */
final class TlRpcDropAnswerRpcAnswerDroppedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRpcDropAnswerRpcAnswerDropped> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRpcDropAnswerRpcAnswerDropped::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'msg_id' => 1001,
            'seq_no' => 2,
            'bytes' => 3,
        ];
    }
}
