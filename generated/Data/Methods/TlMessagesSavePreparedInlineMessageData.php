<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.savePreparedInlineMessage (crc32 f21f7f2f), returns messages.BotPreparedInlineMessage. */
final class TlMessagesSavePreparedInlineMessageData extends Data
{
    public const METHOD = 'messages.savePreparedInlineMessage';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $result,
    public mixed $userId,
    public ?array $peerTypes,
    ) {
    }
}
