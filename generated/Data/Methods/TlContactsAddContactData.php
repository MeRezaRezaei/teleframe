<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method contacts.addContact (crc32 d9ba2e54), returns Updates. */
final class TlContactsAddContactData extends Data
{
    public const METHOD = 'contacts.addContact';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $addPhonePrivacyException,
    public mixed $id,
    public string $firstName,
    public string $lastName,
    public string $phone,
    public mixed $note,
    ) {
    }
}
