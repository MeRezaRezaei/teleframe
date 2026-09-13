<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for stories.allStoriesNotModified of stories.AllStories.
 */
final class TlStoriesAllStoriesNotModifiedData extends TlStoriesAllStoriesAbstractData
{
    public function __construct(
    public int $flags,
    public string $state,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStoriesStealthModeAbstractData $stealthMode,
    ) {
    }
}
