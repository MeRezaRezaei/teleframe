<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method users.setSecureValueErrors (crc32 90c894b5), returns Bool. */
final class TlUsersSetSecureValueErrorsData extends Data
{
    public const METHOD = 'users.setSecureValueErrors';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $id,
    public array $errors,
    ) {
    }
}
