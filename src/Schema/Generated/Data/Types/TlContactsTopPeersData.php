<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for contacts.topPeers of contacts.TopPeers.
 */
final class TlContactsTopPeersData extends TlContactsTopPeersAbstractData
{
    public function __construct(
    public array $categories,
    public array $chats,
    public array $users,
    ) {
    }
}
