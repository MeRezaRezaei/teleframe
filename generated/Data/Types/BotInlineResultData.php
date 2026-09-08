<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for botInlineResult of BotInlineResult.
 */
final class BotInlineResultData extends TlBotInlineResultAbstractData
{
    public function __construct(
    public int $flags,
    public string $id,
    public string $type,
    public ?string $title,
    public ?string $description,
    public ?string $url,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlWebDocumentAbstractData $thumb,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlWebDocumentAbstractData $content,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBotInlineMessageAbstractData $sendMessage,
    ) {
    }
}
