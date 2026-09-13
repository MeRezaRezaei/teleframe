<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputChatUploadedPhoto of InputChatPhoto.
 */
final class InputChatUploadedPhotoData extends TlInputChatPhotoAbstractData
{
    public function __construct(
    public int $flags,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputFileAbstractData $file,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputFileAbstractData $video,
    public ?float $videoStartTs,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlVideoSizeAbstractData $videoEmojiMarkup,
    ) {
    }
}
