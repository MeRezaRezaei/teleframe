<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.botPreparedInlineMessage of messages.BotPreparedInlineMessage.
 */
final class TlMessagesBotPreparedInlineMessageData extends TlMessagesBotPreparedInlineMessageAbstractData
{
    public function __construct(
    public string $id,
    public int $expireDate,
    ) {
    }
}
