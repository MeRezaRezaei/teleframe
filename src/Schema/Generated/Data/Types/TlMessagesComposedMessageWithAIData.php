<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.composedMessageWithAI of messages.ComposedMessageWithAI.
 */
final class TlMessagesComposedMessageWithAIData extends TlMessagesComposedMessageWithAIAbstractData
{
    public function __construct(
    public int $flags,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlTextWithEntitiesAbstractData $resultText,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlTextWithEntitiesAbstractData $diffText,
    ) {
    }
}
