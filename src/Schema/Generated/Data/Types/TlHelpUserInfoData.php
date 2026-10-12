<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for help.userInfo of help.UserInfo.
 */
final class TlHelpUserInfoData extends TlHelpUserInfoAbstractData
{
    public function __construct(
    public string $message,
    public array $entities,
    public string $author,
    public int $date,
    ) {
    }
}
