<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStickerSetStickerSet (stickerSet). */
final class TlStickerSetStickerSetFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSetStickerSet> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSetStickerSet::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'archived' => true,
            'official' => true,
            'masks' => true,
            'emojis' => true,
            'text_color' => true,
            'channel_emoji_status' => true,
            'creator' => true,
            'installed_date' => 9,
            'tl_id' => 1010,
            'access_hash' => 1011,
            'title' => 'title-12',
            'short_name' => 'short_name-13',
            'thumb_dc_id' => 14,
            'thumb_version' => 15,
            'thumb_document_id' => 1016,
            'count' => 17,
            'hash' => 18,
        ];
    }
}
