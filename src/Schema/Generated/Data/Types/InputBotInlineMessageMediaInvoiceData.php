<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputBotInlineMessageMediaInvoice of InputBotInlineMessage.
 *
 * bytes params carried as base64 strings: payload
 */
final class InputBotInlineMessageMediaInvoiceData extends TlInputBotInlineMessageAbstractData
{
    public function __construct(
    public int $flags,
    public string $title,
    public string $description,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputWebDocumentAbstractData $photo,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInvoiceAbstractData $invoice,
    public string $payload,
    public string $provider,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDataJSONAbstractData $providerData,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlReplyMarkupAbstractData $replyMarkup,
    ) {
    }
}
