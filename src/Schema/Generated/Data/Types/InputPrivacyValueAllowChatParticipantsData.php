<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputPrivacyValueAllowChatParticipants of InputPrivacyRule.
 */
final class InputPrivacyValueAllowChatParticipantsData extends TlInputPrivacyRuleAbstractData
{
    public function __construct(
    public array $chats,
    ) {
    }
}
