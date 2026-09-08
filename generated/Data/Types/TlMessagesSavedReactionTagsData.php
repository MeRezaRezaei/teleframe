<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.savedReactionTags of messages.SavedReactionTags.
 */
final class TlMessagesSavedReactionTagsData extends TlMessagesSavedReactionTagsAbstractData
{
    public function __construct(
    public array $tags,
    public int $hash,
    ) {
    }
}
