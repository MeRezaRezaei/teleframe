<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputReplyToMessage of InputReplyTo.
 *
 * bytes params carried as base64 strings: poll_option
 */
final class InputReplyToMessageData extends TlInputReplyToAbstractData
{
    public function __construct(
    public int $flags,
    public int $replyToMsgId,
    public ?int $topMsgId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputPeerAbstractData $replyToPeerId,
    public ?string $quoteText,
    public ?array $quoteEntities,
    public ?int $quoteOffset,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputPeerAbstractData $monoforumPeerId,
    public ?int $todoItemId,
    public ?string $pollOption,
    ) {
    }
}
