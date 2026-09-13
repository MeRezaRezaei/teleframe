<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for stories.albums of stories.Albums.
 */
final class TlStoriesAlbumsData extends TlStoriesAlbumsAbstractData
{
    public function __construct(
    public int $hash,
    public array $albums,
    ) {
    }
}
