<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateBotInlineSend of Update.
 */
final class UpdateBotInlineSendData extends TlUpdateAbstractData
{
    public function __construct(
    public int $flags,
    public int $userId,
    public string $query,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlGeoPointAbstractData $geo,
    public string $id,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputBotInlineMessageIDAbstractData $msgId,
    ) {
    }
}
