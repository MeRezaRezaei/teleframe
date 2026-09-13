<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.saveMusic (crc32 b26732a9), returns Bool. */
final class TlAccountSaveMusicData extends Data
{
    public const METHOD = 'account.saveMusic';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $unsave,
    public mixed $id,
    public mixed $afterId,
    ) {
    }
}
