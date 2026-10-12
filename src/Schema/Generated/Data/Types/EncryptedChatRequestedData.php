<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for encryptedChatRequested of EncryptedChat.
 *
 * bytes params carried as base64 strings: g_a
 */
final class EncryptedChatRequestedData extends TlEncryptedChatAbstractData
{
    public function __construct(
    public int $flags,
    public ?int $folderId,
    public int $id,
    public int $accessHash,
    public int $date,
    public int $adminId,
    public int $participantId,
    public string $gA,
    ) {
    }
}
