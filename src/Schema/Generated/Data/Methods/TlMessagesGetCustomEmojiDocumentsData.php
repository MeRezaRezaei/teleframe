<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getCustomEmojiDocuments (crc32 d9ab0f54), returns Vector<Document>. */
final class TlMessagesGetCustomEmojiDocumentsData extends Data
{
    public const METHOD = 'messages.getCustomEmojiDocuments';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public array $documentId,
    ) {
    }
}
