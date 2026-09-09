<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for inputInvoiceChatInviteSubscription of InputInvoice (crc32 34e793f1). */
final class TlInputInvoiceInputInvoiceChatInviteSubscription extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_invoice_input_invoice_chat_invite_subscription';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'hash' => 'string',
    ];
}
