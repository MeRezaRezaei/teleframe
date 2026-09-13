<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for chatInviteExported of ExportedChatInvite.
 */
final class ChatInviteExportedData extends TlExportedChatInviteAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'revoked' => ['flags', 0],
        'permanent' => ['flags', 5],
        'requestNeeded' => ['flags', 6],
    ];

    public function __construct(
    public int $flags,
    public ?bool $revoked,
    public ?bool $permanent,
    public ?bool $requestNeeded,
    public string $link,
    public int $adminId,
    public int $date,
    public ?int $startDate,
    public ?int $expireDate,
    public ?int $usageLimit,
    public ?int $usage,
    public ?int $requested,
    public ?int $subscriptionExpired,
    public ?string $title,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarsSubscriptionPricingAbstractData $subscriptionPricing,
    ) {
    }
}
