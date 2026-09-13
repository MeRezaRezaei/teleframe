<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputBotInlineMessageID64 of InputBotInlineMessageID.
 */
final class InputBotInlineMessageID64Data extends TlInputBotInlineMessageIDAbstractData
{
    public function __construct(
    public int $dcId,
    public int $ownerId,
    public int $id,
    public int $accessHash,
    ) {
    }
}
