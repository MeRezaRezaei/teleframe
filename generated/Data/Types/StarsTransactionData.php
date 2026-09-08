<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for starsTransaction of StarsTransaction.
 *
 * bytes params carried as base64 strings: bot_payload
 */
final class StarsTransactionData extends TlStarsTransactionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'refund' => ['flags', 3],
        'pending' => ['flags', 4],
        'failed' => ['flags', 6],
        'gift' => ['flags', 10],
        'reaction' => ['flags', 11],
        'stargiftUpgrade' => ['flags', 18],
        'businessTransfer' => ['flags', 21],
        'stargiftResale' => ['flags', 22],
        'postsSearch' => ['flags', 24],
        'stargiftPrepaidUpgrade' => ['flags', 25],
        'stargiftDropOriginalDetails' => ['flags', 26],
        'phonegroupMessage' => ['flags', 27],
        'stargiftAuctionBid' => ['flags', 28],
        'offer' => ['flags', 29],
    ];

    public function __construct(
    public int $flags,
    public ?bool $refund,
    public ?bool $pending,
    public ?bool $failed,
    public ?bool $gift,
    public ?bool $reaction,
    public ?bool $stargiftUpgrade,
    public ?bool $businessTransfer,
    public ?bool $stargiftResale,
    public ?bool $postsSearch,
    public ?bool $stargiftPrepaidUpgrade,
    public ?bool $stargiftDropOriginalDetails,
    public ?bool $phonegroupMessage,
    public ?bool $stargiftAuctionBid,
    public ?bool $offer,
    public string $id,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarsAmountAbstractData $amount,
    public int $date,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarsTransactionPeerAbstractData $peer,
    public ?string $title,
    public ?string $description,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlWebDocumentAbstractData $photo,
    public ?int $transactionDate,
    public ?string $transactionUrl,
    public ?string $botPayload,
    public ?int $msgId,
    public ?array $extendedMedia,
    public ?int $subscriptionPeriod,
    public ?int $giveawayPostId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarGiftAbstractData $stargift,
    public ?int $floodskipNumber,
    public ?int $starrefCommissionPermille,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $starrefPeer,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarsAmountAbstractData $starrefAmount,
    public ?int $paidMessages,
    public ?int $premiumGiftMonths,
    public ?int $adsProceedsFromDate,
    public ?int $adsProceedsToDate,
    ) {
    }
}
