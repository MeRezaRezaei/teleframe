<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.createChannel (crc32 91006707), returns Updates. */
final class TlChannelsCreateChannelData extends Data
{
    public const METHOD = 'channels.createChannel';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $broadcast,
    public ?bool $megagroup,
    public ?bool $forImport,
    public ?bool $forum,
    public string $title,
    public string $about,
    public mixed $geoPoint,
    public ?string $address,
    public ?int $ttlPeriod,
    ) {
    }
}
