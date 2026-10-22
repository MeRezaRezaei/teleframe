<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.peerDialogs of messages.PeerDialogs.
 */
final class TlMessagesPeerDialogsData extends TlMessagesPeerDialogsAbstractData
{
    public function __construct(
    public array $dialogs,
    public array $messages,
    public array $chats,
    public array $users,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlUpdatesStateAbstractData $state,
    ) {
    }
}
