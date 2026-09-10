<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputFolderPeerInputFolderPeer (inputFolderPeer). */
final class TlInputFolderPeerInputFolderPeerFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputFolderPeerInputFolderPeer> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputFolderPeerInputFolderPeer::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'peer' => 1001,
            'folder_id' => 2,
        ];
    }
}
