<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getNotifyExceptions (crc32 53577479), returns Updates. */
final class TlAccountGetNotifyExceptionsData extends Data
{
    public const METHOD = 'account.getNotifyExceptions';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $compareSound,
    public ?bool $compareStories,
    public mixed $peer,
    ) {
    }
}
