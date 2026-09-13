<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.toggleDialogFilterTags (crc32 fd2dda49), returns Bool. */
final class TlMessagesToggleDialogFilterTagsData extends Data
{
    public const METHOD = 'messages.toggleDialogFilterTags';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $enabled,
    ) {
    }
}
