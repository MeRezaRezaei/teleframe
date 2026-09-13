<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputPrivacyValueDisallowUsers of InputPrivacyRule.
 */
final class InputPrivacyValueDisallowUsersData extends TlInputPrivacyRuleAbstractData
{
    public function __construct(
    public array $users,
    ) {
    }
}
