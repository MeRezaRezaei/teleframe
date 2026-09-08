<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateUserTyping of Update.
 */
final class UpdateUserTypingData extends TlUpdateAbstractData
{
    public function __construct(
    public int $flags,
    public int $userId,
    public ?int $topMsgId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSendMessageActionAbstractData $action,
    ) {
    }
}
