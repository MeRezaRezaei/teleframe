<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.deleteGroupCallMessages (crc32 f64f54f7), returns Updates. */
final class TlPhoneDeleteGroupCallMessagesData extends Data
{
    public const METHOD = 'phone.deleteGroupCallMessages';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $reportSpam,
    public mixed $call,
    public array $messages,
    ) {
    }
}
