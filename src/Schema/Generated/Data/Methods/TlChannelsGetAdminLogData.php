<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.getAdminLog (crc32 33ddf480), returns channels.AdminLogResults. */
final class TlChannelsGetAdminLogData extends Data
{
    public const METHOD = 'channels.getAdminLog';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $channel,
    public string $q,
    public mixed $eventsFilter,
    public ?array $admins,
    public int $maxId,
    public int $minId,
    public int $limit,
    ) {
    }
}
