<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for contacts.sponsoredPeers of contacts.SponsoredPeers.
 */
final class TlContactsSponsoredPeersData extends TlContactsSponsoredPeersAbstractData
{
    public function __construct(
    public array $peers,
    public array $chats,
    public array $users,
    ) {
    }
}
