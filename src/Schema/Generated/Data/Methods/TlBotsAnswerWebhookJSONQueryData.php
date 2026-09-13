<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.answerWebhookJSONQuery (crc32 e6213f4d), returns Bool. */
final class TlBotsAnswerWebhookJSONQueryData extends Data
{
    public const METHOD = 'bots.answerWebhookJSONQuery';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $queryId,
    public mixed $data,
    ) {
    }
}
