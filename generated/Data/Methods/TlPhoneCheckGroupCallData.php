<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.checkGroupCall (crc32 b59cf977), returns Vector<int>. */
final class TlPhoneCheckGroupCallData extends Data
{
    public const METHOD = 'phone.checkGroupCall';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $call,
    public array $sources,
    ) {
    }
}
