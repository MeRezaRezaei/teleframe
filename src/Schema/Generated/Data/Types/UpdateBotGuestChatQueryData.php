<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateBotGuestChatQuery of Update.
 */
final class UpdateBotGuestChatQueryData extends TlUpdateAbstractData
{
    public function __construct(
    public int $flags,
    public int $queryId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageAbstractData $message,
    public ?array $referenceMessages,
    public int $qts,
    ) {
    }
}
