<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsValidatedRequestedInfoValidate9668a5a19280Shipping_options;

/** Constructor model for payments.validatedRequestedInfo of payments.ValidatedRequestedInfo (crc32 d1451883). */
final class TlPaymentsValidatedRequestedInfoValidatedRequestedInfo extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_payments_validated_requested_info_validate_9668a5a19280';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'tl_id' => 'string',
    ];

    public function shippingOptions(): HasMany
    {
        return $this->tlChild(TlPaymentsValidatedRequestedInfoValidate9668a5a19280Shipping_options::class);
    }
}
