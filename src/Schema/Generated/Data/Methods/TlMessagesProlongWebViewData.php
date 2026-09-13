<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.prolongWebView (crc32 b0d81a83), returns Bool. */
final class TlMessagesProlongWebViewData extends Data
{
    public const METHOD = 'messages.prolongWebView';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $silent,
    public mixed $peer,
    public mixed $bot,
    public int $queryId,
    public mixed $replyTo,
    public mixed $sendAs,
    ) {
    }
}
