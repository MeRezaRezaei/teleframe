<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.requestUrlAuth (crc32 894cc99c), returns UrlAuthResult. */
final class TlMessagesRequestUrlAuthData extends Data
{
    public const METHOD = 'messages.requestUrlAuth';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public ?int $msgId,
    public ?int $buttonId,
    public ?string $url,
    public ?string $inAppOrigin,
    ) {
    }
}
