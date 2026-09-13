<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for botInlineMessageMediaInvoice of BotInlineMessage.
 */
final class BotInlineMessageMediaInvoiceData extends TlBotInlineMessageAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'shippingAddressRequested' => ['flags', 1],
        'test' => ['flags', 3],
    ];

    public function __construct(
    public int $flags,
    public ?bool $shippingAddressRequested,
    public ?bool $test,
    public string $title,
    public string $description,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlWebDocumentAbstractData $photo,
    public string $currency,
    public int $totalAmount,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlReplyMarkupAbstractData $replyMarkup,
    ) {
    }
}
