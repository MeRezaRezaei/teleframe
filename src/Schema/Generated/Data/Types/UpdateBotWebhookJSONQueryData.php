<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateBotWebhookJSONQuery of Update.
 */
final class UpdateBotWebhookJSONQueryData extends TlUpdateAbstractData
{
    public function __construct(
    public int $queryId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDataJSONAbstractData $data,
    public int $timeout,
    ) {
    }
}
