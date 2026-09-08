<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/**
 * Union DTO base for TL type InputInvoice.
 *
 * @method static static hydrate(array $payload)
 */
abstract class TlInputInvoiceAbstractData extends Data
{
    /** @var array<string, class-string<self>> */
    protected const DISPATCH = [
        'inputInvoiceBusinessBotTransferStars' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputInvoiceBusinessBotTransferStarsData::class,
        'inputInvoiceChatInviteSubscription' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputInvoiceChatInviteSubscriptionData::class,
        'inputInvoiceMessage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputInvoiceMessageData::class,
        'inputInvoicePremiumAuthCode' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputInvoicePremiumAuthCodeData::class,
        'inputInvoicePremiumGiftCode' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputInvoicePremiumGiftCodeData::class,
        'inputInvoicePremiumGiftStars' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputInvoicePremiumGiftStarsData::class,
        'inputInvoiceSlug' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputInvoiceSlugData::class,
        'inputInvoiceStarGift' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputInvoiceStarGiftData::class,
        'inputInvoiceStarGiftAuctionBid' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputInvoiceStarGiftAuctionBidData::class,
        'inputInvoiceStarGiftDropOriginalDetails' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputInvoiceStarGiftDropOriginalDetailsData::class,
        'inputInvoiceStarGiftPrepaidUpgrade' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputInvoiceStarGiftPrepaidUpgradeData::class,
        'inputInvoiceStarGiftResale' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputInvoiceStarGiftResaleData::class,
        'inputInvoiceStarGiftTransfer' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputInvoiceStarGiftTransferData::class,
        'inputInvoiceStarGiftUpgrade' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputInvoiceStarGiftUpgradeData::class,
        'inputInvoiceStars' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputInvoiceStarsData::class,
    ];

    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [];

    /** Dispatch on the constructor name carried under the '_' key of a decoded wire payload. */
    public static function hydrate(array $payload): static
    {
        $class = static::DISPATCH[$payload['_']]
            ?? throw new \InvalidArgumentException('Unknown constructor ' . $payload['_'] . ' for InputInvoice');
        foreach ((new \ReflectionMethod($class, '__construct'))->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $payload)) {
                continue;
            }
            $bits = $class::TL_FLAG_BITS[$name] ?? null;
            if ($bits !== null) {
                $word = (int) ($payload[$bits[0]] ?? 0);
                $payload[$name] = (bool) ($word >> $bits[1] & 1);
                continue;
            }
            $wireKey = self::tlWireKey($name);
            $payload[$name] = array_key_exists($wireKey, $payload) ? $payload[$wireKey] : null;
        }
        /** @var static */
        return $class::from($payload);
    }

    /** camelCase constructor param name to snake_case wire key (regex-free). */
    private static function tlWireKey(string $name): string
    {
        $out = '';
        foreach (str_split($name) as $i => $ch) {
            $out .= $i > 0 && $ch >= 'A' && $ch <= 'Z' ? '_' . strtolower($ch) : $ch;
        }
        return $out;
    }
}
