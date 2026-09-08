<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for payments.paymentForm of payments.PaymentForm.
 */
final class TlPaymentsPaymentFormData extends TlPaymentsPaymentFormAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'canSaveCredentials' => ['flags', 2],
        'passwordMissing' => ['flags', 3],
    ];

    public function __construct(
    public int $flags,
    public ?bool $canSaveCredentials,
    public ?bool $passwordMissing,
    public int $formId,
    public int $botId,
    public string $title,
    public string $description,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlWebDocumentAbstractData $photo,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInvoiceAbstractData $invoice,
    public int $providerId,
    public string $url,
    public ?string $nativeProvider,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDataJSONAbstractData $nativeParams,
    public ?array $additionalMethods,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPaymentRequestedInfoAbstractData $savedInfo,
    public ?array $savedCredentials,
    public array $users,
    ) {
    }
}
