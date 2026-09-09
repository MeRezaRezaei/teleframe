<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessRecipientsBusinessRecipientsUsers;

/** Constructor model for businessRecipients of BusinessRecipients (crc32 21108ff7). */
final class TlBusinessRecipientsBusinessRecipients extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_business_recipients_business_recipients';

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
        return $this->tlChild(TlBusinessRecipientsBusinessRecipientsUsers::class);
    }
}
