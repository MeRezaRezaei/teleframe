<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBusinessRecipientsInputBusinessRecipientsUsers;

/** Constructor model for inputBusinessRecipients of InputBusinessRecipients (crc32 6f8b32aa). */
final class TlInputBusinessRecipientsInputBusinessRecipients extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_business_recipients_input_business_recipients';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'existing_chats' => 'bool',
        'new_chats' => 'bool',
        'contacts' => 'bool',
        'non_contacts' => 'bool',
        'exclude_selected' => 'bool',
    ];

    public function users(): HasMany
    {
        return $this->tlChild(TlInputBusinessRecipientsInputBusinessRecipientsUsers::class);
    }
}
