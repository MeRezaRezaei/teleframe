<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method contacts.getSponsoredPeers (crc32 b6c8c393), returns contacts.SponsoredPeers. */
final class TlContactsGetSponsoredPeersData extends Data
{
    public const METHOD = 'contacts.getSponsoredPeers';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $q,
    ) {
    }
}
