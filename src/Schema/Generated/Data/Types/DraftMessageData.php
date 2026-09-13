<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for draftMessage of DraftMessage.
 */
final class DraftMessageData extends TlDraftMessageAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'noWebpage' => ['flags', 1],
        'invertMedia' => ['flags', 6],
    ];

    public function __construct(
    public int $flags,
    public ?bool $noWebpage,
    public ?bool $invertMedia,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputReplyToAbstractData $replyTo,
    public string $message,
    public ?array $entities,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputMediaAbstractData $media,
    public int $date,
    public ?int $effect,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSuggestedPostAbstractData $suggestedPost,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlRichMessageAbstractData $richMessage,
    ) {
    }
}
