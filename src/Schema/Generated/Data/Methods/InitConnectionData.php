<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method initConnection (crc32 c1cd5ea9), returns X. */
final class InitConnectionData extends Data
{
    public const METHOD = 'initConnection';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed ${X,
    public int $flags,
    public int $apiId,
    public string $deviceModel,
    public string $systemVersion,
    public string $appVersion,
    public string $systemLangCode,
    public string $langPack,
    public string $langCode,
    public mixed $proxy,
    public mixed $params,
    public mixed $query,
    ) {
    }
}
