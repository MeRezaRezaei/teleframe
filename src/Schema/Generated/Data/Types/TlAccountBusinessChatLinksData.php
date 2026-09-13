<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for account.businessChatLinks of account.BusinessChatLinks.
 */
final class TlAccountBusinessChatLinksData extends TlAccountBusinessChatLinksAbstractData
{
    public function __construct(
    public array $links,
    public array $chats,
    public array $users,
    ) {
    }
}
