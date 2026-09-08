<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method contacts.getTopPeers (crc32 973478b6), returns contacts.TopPeers. */
final class TlContactsGetTopPeersData extends Data
{
    public const METHOD = 'contacts.getTopPeers';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $correspondents,
    public ?bool $botsPm,
    public ?bool $botsInline,
    public ?bool $phoneCalls,
    public ?bool $forwardUsers,
    public ?bool $forwardChats,
    public ?bool $groups,
    public ?bool $channels,
    public ?bool $botsApp,
    public ?bool $botsGuestchat,
    public int $offset,
    public int $limit,
    public int $hash,
    ) {
    }
}
