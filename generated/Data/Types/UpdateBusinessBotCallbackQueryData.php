<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateBusinessBotCallbackQuery of Update.
 *
 * bytes params carried as base64 strings: data
 */
final class UpdateBusinessBotCallbackQueryData extends TlUpdateAbstractData
{
    public function __construct(
    public int $flags,
    public int $queryId,
    public int $userId,
    public string $connectionId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageAbstractData $message,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageAbstractData $replyToMessage,
    public int $chatInstance,
    public ?string $data,
    ) {
    }
}
