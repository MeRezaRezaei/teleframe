<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.setInlineBotResults (crc32 bb12a419), returns Bool. */
final class TlMessagesSetInlineBotResultsData extends Data
{
    public const METHOD = 'messages.setInlineBotResults';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $gallery,
    public ?bool $private,
    public int $queryId,
    public array $results,
    public int $cacheTime,
    public ?string $nextOffset,
    public mixed $switchPm,
    public mixed $switchWebview,
    ) {
    }
}
