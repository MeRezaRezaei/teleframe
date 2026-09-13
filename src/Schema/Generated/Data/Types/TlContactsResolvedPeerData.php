<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for contacts.resolvedPeer of contacts.ResolvedPeer.
 */
final class TlContactsResolvedPeerData extends TlContactsResolvedPeerAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public array $chats,
    public array $users,
    ) {
    }
}
