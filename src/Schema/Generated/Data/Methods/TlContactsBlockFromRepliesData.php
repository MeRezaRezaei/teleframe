<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method contacts.blockFromReplies (crc32 29a8962c), returns Updates. */
final class TlContactsBlockFromRepliesData extends Data
{
    public const METHOD = 'contacts.blockFromReplies';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $deleteMessage,
    public ?bool $deleteHistory,
    public ?bool $reportSpam,
    public int $msgId,
    ) {
    }
}
