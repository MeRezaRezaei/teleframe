<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputMessageEntityMentionName of MessageEntity.
 */
final class InputMessageEntityMentionNameData extends TlMessageEntityAbstractData
{
    public function __construct(
    public int $offset,
    public int $length,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputUserAbstractData $userId,
    ) {
    }
}
