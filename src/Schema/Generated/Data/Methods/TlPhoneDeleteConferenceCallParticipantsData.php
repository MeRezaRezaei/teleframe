<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.deleteConferenceCallParticipants (crc32 8ca60525), returns Updates. */
final class TlPhoneDeleteConferenceCallParticipantsData extends Data
{
    public const METHOD = 'phone.deleteConferenceCallParticipants';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $onlyLeft,
    public ?bool $kick,
    public mixed $call,
    public array $ids,
    public string $block,
    ) {
    }
}
