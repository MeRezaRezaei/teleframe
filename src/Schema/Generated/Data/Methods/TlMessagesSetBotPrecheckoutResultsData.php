<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.setBotPrecheckoutResults (crc32 09c2dd95), returns Bool. */
final class TlMessagesSetBotPrecheckoutResultsData extends Data
{
    public const METHOD = 'messages.setBotPrecheckoutResults';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $success,
    public int $queryId,
    public ?string $error,
    ) {
    }
}
