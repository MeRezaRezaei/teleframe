<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.validateRequestedInfo (crc32 b6c8f12b), returns payments.ValidatedRequestedInfo. */
final class TlPaymentsValidateRequestedInfoData extends Data
{
    public const METHOD = 'payments.validateRequestedInfo';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $save,
    public mixed $invoice,
    public mixed $info,
    ) {
    }
}
