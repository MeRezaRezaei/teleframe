<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.preparedInlineMessage of messages.PreparedInlineMessage.
 */
final class TlMessagesPreparedInlineMessageData extends TlMessagesPreparedInlineMessageAbstractData
{
    public function __construct(
    public int $queryId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBotInlineResultAbstractData $result,
    public array $peerTypes,
    public int $cacheTime,
    public array $users,
    ) {
    }
}
