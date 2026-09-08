<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputBotInlineResultPhoto of InputBotInlineResult.
 */
final class InputBotInlineResultPhotoData extends TlInputBotInlineResultAbstractData
{
    public function __construct(
    public string $id,
    public string $type,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputPhotoAbstractData $photo,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputBotInlineMessageAbstractData $sendMessage,
    ) {
    }
}
