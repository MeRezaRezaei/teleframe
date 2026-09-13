<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateGroupCallEncryptedMessage of Update.
 *
 * bytes params carried as base64 strings: encrypted_message
 */
final class UpdateGroupCallEncryptedMessageData extends TlUpdateAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputGroupCallAbstractData $call,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $fromId,
    public string $encryptedMessage,
    ) {
    }
}
