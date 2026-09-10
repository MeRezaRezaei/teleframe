<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlFolderPeerFolderPeer (folderPeer). */
final class TlFolderPeerFolderPeerFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlFolderPeerFolderPeer> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlFolderPeerFolderPeer::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'peer' => 1001,
            'folder_id' => 2,
        ];
    }
}
