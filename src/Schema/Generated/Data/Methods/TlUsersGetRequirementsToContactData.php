<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method users.getRequirementsToContact (crc32 d89a83a3), returns Vector<RequirementToContact>. */
final class TlUsersGetRequirementsToContactData extends Data
{
    public const METHOD = 'users.getRequirementsToContact';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public array $id,
    ) {
    }
}
