<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for chatAdminRights of ChatAdminRights.
 */
final class ChatAdminRightsData extends TlChatAdminRightsAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'changeInfo' => ['flags', 0],
        'postMessages' => ['flags', 1],
        'editMessages' => ['flags', 2],
        'deleteMessages' => ['flags', 3],
        'banUsers' => ['flags', 4],
        'inviteUsers' => ['flags', 5],
        'pinMessages' => ['flags', 7],
        'addAdmins' => ['flags', 9],
        'anonymous' => ['flags', 10],
        'manageCall' => ['flags', 11],
        'other' => ['flags', 12],
        'manageTopics' => ['flags', 13],
        'postStories' => ['flags', 14],
        'editStories' => ['flags', 15],
        'deleteStories' => ['flags', 16],
        'manageDirectMessages' => ['flags', 17],
        'manageRanks' => ['flags', 18],
    ];

    public function __construct(
    public int $flags,
    public ?bool $changeInfo,
    public ?bool $postMessages,
    public ?bool $editMessages,
    public ?bool $deleteMessages,
    public ?bool $banUsers,
    public ?bool $inviteUsers,
    public ?bool $pinMessages,
    public ?bool $addAdmins,
    public ?bool $anonymous,
    public ?bool $manageCall,
    public ?bool $other,
    public ?bool $manageTopics,
    public ?bool $postStories,
    public ?bool $editStories,
    public ?bool $deleteStories,
    public ?bool $manageDirectMessages,
    public ?bool $manageRanks,
    ) {
    }
}
