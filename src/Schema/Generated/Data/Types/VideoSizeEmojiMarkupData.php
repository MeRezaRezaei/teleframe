<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for videoSizeEmojiMarkup of VideoSize.
 */
final class VideoSizeEmojiMarkupData extends TlVideoSizeAbstractData
{
    public function __construct(
    public int $emojiId,
    public array $backgroundColors,
    ) {
    }
}
