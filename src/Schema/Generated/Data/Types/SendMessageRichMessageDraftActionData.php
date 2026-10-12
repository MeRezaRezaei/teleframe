<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for sendMessageRichMessageDraftAction of SendMessageAction.
 */
final class SendMessageRichMessageDraftActionData extends TlSendMessageActionAbstractData
{
    public function __construct(
    public int $randomId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlRichMessageAbstractData $richMessage,
    ) {
    }
}
