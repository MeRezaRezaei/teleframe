<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.setInlineGameScore (crc32 15ad9f64), returns Bool. */
final class TlMessagesSetInlineGameScoreData extends Data
{
    public const METHOD = 'messages.setInlineGameScore';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $editMessage,
    public ?bool $force,
    public mixed $id,
    public mixed $userId,
    public int $score,
    ) {
    }
}
