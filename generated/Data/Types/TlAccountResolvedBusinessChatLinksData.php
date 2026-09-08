<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for account.resolvedBusinessChatLinks of account.ResolvedBusinessChatLinks.
 */
final class TlAccountResolvedBusinessChatLinksData extends TlAccountResolvedBusinessChatLinksAbstractData
{
    public function __construct(
    public int $flags,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public string $message,
    public ?array $entities,
    public array $chats,
    public array $users,
    ) {
    }
}
