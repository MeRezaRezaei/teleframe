<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateBotCallbackQuery of Update.
 *
 * bytes params carried as base64 strings: data
 */
final class UpdateBotCallbackQueryData extends TlUpdateAbstractData
{
    public function __construct(
    public int $flags,
    public int $queryId,
    public int $userId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public int $msgId,
    public int $chatInstance,
    public ?string $data,
    public ?string $gameShortName,
    ) {
    }
}
