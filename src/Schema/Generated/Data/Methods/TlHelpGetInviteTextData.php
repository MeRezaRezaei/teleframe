<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method help.getInviteText (crc32 4d392343), returns help.InviteText. */
final class TlHelpGetInviteTextData extends Data
{
    public const METHOD = 'help.getInviteText';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
