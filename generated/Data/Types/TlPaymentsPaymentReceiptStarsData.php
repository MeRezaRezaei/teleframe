<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for payments.paymentReceiptStars of payments.PaymentReceipt.
 */
final class TlPaymentsPaymentReceiptStarsData extends TlPaymentsPaymentReceiptAbstractData
{
    public function __construct(
    public int $flags,
    public int $date,
    public int $botId,
    public string $title,
    public string $description,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlWebDocumentAbstractData $photo,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInvoiceAbstractData $invoice,
    public string $currency,
    public int $totalAmount,
    public string $transactionId,
    public array $users,
    ) {
    }
}
