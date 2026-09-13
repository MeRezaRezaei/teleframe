<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for account.privacyRules of account.PrivacyRules.
 */
final class TlAccountPrivacyRulesData extends TlAccountPrivacyRulesAbstractData
{
    public function __construct(
    public array $rules,
    public array $chats,
    public array $users,
    ) {
    }
}
