<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelAdminLogEventsFilter of ChannelAdminLogEventsFilter.
 */
final class ChannelAdminLogEventsFilterData extends TlChannelAdminLogEventsFilterAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'join' => ['flags', 0],
        'leave' => ['flags', 1],
        'invite' => ['flags', 2],
        'ban' => ['flags', 3],
        'unban' => ['flags', 4],
        'kick' => ['flags', 5],
        'unkick' => ['flags', 6],
        'promote' => ['flags', 7],
        'demote' => ['flags', 8],
        'info' => ['flags', 9],
        'settings' => ['flags', 10],
        'pinned' => ['flags', 11],
        'edit' => ['flags', 12],
        'delete' => ['flags', 13],
        'groupCall' => ['flags', 14],
        'invites' => ['flags', 15],
        'send' => ['flags', 16],
        'forums' => ['flags', 17],
        'subExtend' => ['flags', 18],
        'editRank' => ['flags', 19],
    ];

    public function __construct(
    public int $flags,
    public ?bool $join,
    public ?bool $leave,
    public ?bool $invite,
    public ?bool $ban,
    public ?bool $unban,
    public ?bool $kick,
    public ?bool $unkick,
    public ?bool $promote,
    public ?bool $demote,
    public ?bool $info,
    public ?bool $settings,
    public ?bool $pinned,
    public ?bool $edit,
    public ?bool $delete,
    public ?bool $groupCall,
    public ?bool $invites,
    public ?bool $send,
    public ?bool $forums,
    public ?bool $subExtend,
    public ?bool $editRank,
    ) {
    }
}
