<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputBotInlineResultDocument of InputBotInlineResult.
 */
final class InputBotInlineResultDocumentData extends TlInputBotInlineResultAbstractData
{
    public function __construct(
    public int $flags,
    public string $id,
    public string $type,
    public ?string $title,
    public ?string $description,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputDocumentAbstractData $document,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputBotInlineMessageAbstractData $sendMessage,
    ) {
    }
}
