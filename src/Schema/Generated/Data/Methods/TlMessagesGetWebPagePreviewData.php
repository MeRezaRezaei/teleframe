<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getWebPagePreview (crc32 570d6f6f), returns messages.WebPagePreview. */
final class TlMessagesGetWebPagePreviewData extends Data
{
    public const METHOD = 'messages.getWebPagePreview';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public string $message,
    public ?array $entities,
    ) {
    }
}
