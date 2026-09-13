<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.webPage of messages.WebPage.
 */
final class TlMessagesWebPageData extends TlMessagesWebPageAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlWebPageAbstractData $webpage,
    public array $chats,
    public array $users,
    ) {
    }
}
