<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.editAdmin (crc32 9a98ad68), returns Updates. */
final class TlChannelsEditAdminData extends Data
{
    public const METHOD = 'channels.editAdmin';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $channel,
    public mixed $userId,
    public mixed $adminRights,
    public ?string $rank,
    ) {
    }
}
