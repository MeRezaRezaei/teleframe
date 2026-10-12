<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateBotNewBusinessMessage of Update.
 */
final class UpdateBotNewBusinessMessageData extends TlUpdateAbstractData
{
    public function __construct(
    public int $flags,
    public string $connectionId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageAbstractData $message,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageAbstractData $replyToMessage,
    public int $qts,
    ) {
    }
}
