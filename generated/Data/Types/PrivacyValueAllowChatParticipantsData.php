<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for privacyValueAllowChatParticipants of PrivacyRule.
 */
final class PrivacyValueAllowChatParticipantsData extends TlPrivacyRuleAbstractData
{
    public function __construct(
    public array $chats,
    ) {
    }
}
