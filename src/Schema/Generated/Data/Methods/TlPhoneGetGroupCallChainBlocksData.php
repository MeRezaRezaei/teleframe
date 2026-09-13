<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.getGroupCallChainBlocks (crc32 ee9f88a6), returns Updates. */
final class TlPhoneGetGroupCallChainBlocksData extends Data
{
    public const METHOD = 'phone.getGroupCallChainBlocks';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $call,
    public int $subChainId,
    public int $offset,
    public int $limit,
    ) {
    }
}
