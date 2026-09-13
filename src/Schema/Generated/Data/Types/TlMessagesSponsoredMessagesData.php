<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.sponsoredMessages of messages.SponsoredMessages.
 */
final class TlMessagesSponsoredMessagesData extends TlMessagesSponsoredMessagesAbstractData
{
    public function __construct(
    public int $flags,
    public ?int $postsBetween,
    public ?int $startDelay,
    public ?int $betweenDelay,
    public array $messages,
    public array $chats,
    public array $users,
    ) {
    }
}
