<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for chatlists.chatlistInvite of chatlists.ChatlistInvite.
 */
final class TlChatlistsChatlistInviteData extends TlChatlistsChatlistInviteAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'titleNoanimate' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $titleNoanimate,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlTextWithEntitiesAbstractData $title,
    public ?string $emoticon,
    public array $peers,
    public array $chats,
    public array $users,
    ) {
    }
}
