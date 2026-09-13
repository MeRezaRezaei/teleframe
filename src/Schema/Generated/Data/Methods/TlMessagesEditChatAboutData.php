<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.editChatAbout (crc32 def60797), returns Bool. */
final class TlMessagesEditChatAboutData extends Data
{
    public const METHOD = 'messages.editChatAbout';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public string $about,
    ) {
    }
}
