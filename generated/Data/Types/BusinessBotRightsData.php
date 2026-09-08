<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for businessBotRights of BusinessBotRights.
 */
final class BusinessBotRightsData extends TlBusinessBotRightsAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'reply' => ['flags', 0],
        'readMessages' => ['flags', 1],
        'deleteSentMessages' => ['flags', 2],
        'deleteReceivedMessages' => ['flags', 3],
        'editName' => ['flags', 4],
        'editBio' => ['flags', 5],
        'editProfilePhoto' => ['flags', 6],
        'editUsername' => ['flags', 7],
        'viewGifts' => ['flags', 8],
        'sellGifts' => ['flags', 9],
        'changeGiftSettings' => ['flags', 10],
        'transferAndUpgradeGifts' => ['flags', 11],
        'transferStars' => ['flags', 12],
        'manageStories' => ['flags', 13],
    ];

    public function __construct(
    public int $flags,
    public ?bool $reply,
    public ?bool $readMessages,
    public ?bool $deleteSentMessages,
    public ?bool $deleteReceivedMessages,
    public ?bool $editName,
    public ?bool $editBio,
    public ?bool $editProfilePhoto,
    public ?bool $editUsername,
    public ?bool $viewGifts,
    public ?bool $sellGifts,
    public ?bool $changeGiftSettings,
    public ?bool $transferAndUpgradeGifts,
    public ?bool $transferStars,
    public ?bool $manageStories,
    ) {
    }
}
