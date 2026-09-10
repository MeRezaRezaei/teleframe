<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateGroupCallChainBlocks (updateGroupCallChainBlocks). */
final class TlUpdateUpdateGroupCallChainBlocksFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateGroupCallChainBlocks> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateGroupCallChainBlocks::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'call' => 1001,
            'sub_chain_id' => 2,
            'next_offset' => 3,
        ];
    }
}
