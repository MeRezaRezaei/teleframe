<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method rpc_drop_answer (crc32 58e4a740), returns RpcDropAnswer. */
final class RpcDropAnswerData extends Data
{
    public const METHOD = 'rpc_drop_answer';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $reqMsgId,
    ) {
    }
}
