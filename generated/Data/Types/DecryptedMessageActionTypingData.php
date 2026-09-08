<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for decryptedMessageActionTyping of DecryptedMessageAction.
 */
final class DecryptedMessageActionTypingData extends TlDecryptedMessageActionAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSendMessageActionAbstractData $action,
    ) {
    }
}
