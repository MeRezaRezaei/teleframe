<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.togglePaidReactionPrivacy (crc32 435885b5), returns Bool. */
final class TlMessagesTogglePaidReactionPrivacyData extends Data
{
    public const METHOD = 'messages.togglePaidReactionPrivacy';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $msgId,
    public mixed $private,
    ) {
    }
}
