<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.initTakeoutSession (crc32 8ef3eab0), returns account.Takeout. */
final class TlAccountInitTakeoutSessionData extends Data
{
    public const METHOD = 'account.initTakeoutSession';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $contacts,
    public ?bool $messageUsers,
    public ?bool $messageChats,
    public ?bool $messageMegagroups,
    public ?bool $messageChannels,
    public ?bool $files,
    public ?int $fileMaxSize,
    ) {
    }
}
