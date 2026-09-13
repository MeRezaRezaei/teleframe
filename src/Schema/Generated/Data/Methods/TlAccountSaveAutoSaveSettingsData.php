<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.saveAutoSaveSettings (crc32 d69b8361), returns Bool. */
final class TlAccountSaveAutoSaveSettingsData extends Data
{
    public const METHOD = 'account.saveAutoSaveSettings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $users,
    public ?bool $chats,
    public ?bool $broadcasts,
    public mixed $peer,
    public mixed $settings,
    ) {
    }
}
