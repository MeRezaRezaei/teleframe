<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.getGroupParticipants (crc32 c558d8ab), returns phone.GroupParticipants. */
final class TlPhoneGetGroupParticipantsData extends Data
{
    public const METHOD = 'phone.getGroupParticipants';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $call,
    public array $ids,
    public array $sources,
    public string $offset,
    public int $limit,
    ) {
    }
}
