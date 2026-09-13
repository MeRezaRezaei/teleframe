<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for chatInvite of ChatInvite.
 */
final class ChatInviteData extends TlChatInviteAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'channel' => ['flags', 0],
        'broadcast' => ['flags', 1],
        'public' => ['flags', 2],
        'megagroup' => ['flags', 3],
        'requestNeeded' => ['flags', 6],
        'verified' => ['flags', 7],
        'scam' => ['flags', 8],
        'fake' => ['flags', 9],
        'canRefulfillSubscription' => ['flags', 11],
    ];

    public function __construct(
    public int $flags,
    public ?bool $channel,
    public ?bool $broadcast,
    public ?bool $public,
    public ?bool $megagroup,
    public ?bool $requestNeeded,
    public ?bool $verified,
    public ?bool $scam,
    public ?bool $fake,
    public ?bool $canRefulfillSubscription,
    public string $title,
    public ?string $about,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPhotoAbstractData $photo,
    public int $participantsCount,
    public ?array $participants,
    public int $color,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarsSubscriptionPricingAbstractData $subscriptionPricing,
    public ?int $subscriptionFormId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBotVerificationAbstractData $botVerification,
    ) {
    }
}
