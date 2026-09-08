<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for encryptedChatWaiting of EncryptedChat.
 */
final class EncryptedChatWaitingData extends TlEncryptedChatAbstractData
{
    public function __construct(
    public int $id,
    public int $accessHash,
    public int $date,
    public int $adminId,
    public int $participantId,
    ) {
    }
}
