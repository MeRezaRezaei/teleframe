<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageReplyHeader of MessageReplyHeader.
 *
 * bytes params carried as base64 strings: poll_option
 */
final class MessageReplyHeaderData extends TlMessageReplyHeaderAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'replyToScheduled' => ['flags', 2],
        'forumTopic' => ['flags', 3],
        'quote' => ['flags', 9],
        'replyToEphemeral' => ['flags', 13],
    ];

    public function __construct(
    public int $flags,
    public ?bool $replyToScheduled,
    public ?bool $forumTopic,
    public ?bool $quote,
    public ?bool $replyToEphemeral,
    public ?int $replyToMsgId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $replyToPeerId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageFwdHeaderAbstractData $replyFrom,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageMediaAbstractData $replyMedia,
    public ?int $replyToTopId,
    public ?string $quoteText,
    public ?array $quoteEntities,
    public ?int $quoteOffset,
    public ?int $todoItemId,
    public ?string $pollOption,
    ) {
    }
}
