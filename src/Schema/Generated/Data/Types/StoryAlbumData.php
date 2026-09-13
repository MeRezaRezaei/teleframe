<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for storyAlbum of StoryAlbum.
 */
final class StoryAlbumData extends TlStoryAlbumAbstractData
{
    public function __construct(
    public int $flags,
    public int $albumId,
    public string $title,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPhotoAbstractData $iconPhoto,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $iconVideo,
    ) {
    }
}
