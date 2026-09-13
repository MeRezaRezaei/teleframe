<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputBotInlineResult of InputBotInlineResult.
 */
final class InputBotInlineResultData extends TlInputBotInlineResultAbstractData
{
    public function __construct(
    public int $flags,
    public string $id,
    public string $type,
    public ?string $title,
    public ?string $description,
    public ?string $url,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputWebDocumentAbstractData $thumb,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputWebDocumentAbstractData $content,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputBotInlineMessageAbstractData $sendMessage,
    ) {
    }
}
