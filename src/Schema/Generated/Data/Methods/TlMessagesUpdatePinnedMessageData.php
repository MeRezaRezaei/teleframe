<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.updatePinnedMessage (crc32 d2aaf7ec), returns Updates. */
final class TlMessagesUpdatePinnedMessageData extends Data
{
    public const METHOD = 'messages.updatePinnedMessage';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $silent,
    public ?bool $unpin,
    public ?bool $pmOneside,
    public mixed $peer,
    public int $id,
    ) {
    }
}
