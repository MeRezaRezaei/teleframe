<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.acceptUrlAuth (crc32 67a3f0de), returns UrlAuthResult. */
final class TlMessagesAcceptUrlAuthData extends Data
{
    public const METHOD = 'messages.acceptUrlAuth';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $writeAllowed,
    public ?bool $sharePhoneNumber,
    public mixed $peer,
    public ?int $msgId,
    public ?int $buttonId,
    public ?string $url,
    public ?string $matchCode,
    ) {
    }
}
