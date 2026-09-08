<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for monoForumDialog of SavedDialog.
 */
final class MonoForumDialogData extends TlSavedDialogAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'unreadMark' => ['flags', 3],
        'nopaidMessagesException' => ['flags', 4],
    ];

    public function __construct(
    public int $flags,
    public ?bool $unreadMark,
    public ?bool $nopaidMessagesException,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public int $topMessage,
    public int $readInboxMaxId,
    public int $readOutboxMaxId,
    public int $unreadCount,
    public int $unreadReactionsCount,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDraftMessageAbstractData $draft,
    ) {
    }
}
