<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBusinessBotRecipientsInputBusine36d9b3380d51Users;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBusinessBotRecipientsInputBusine36d9b3380d51Exclude_users;

/** Constructor model for inputBusinessBotRecipients of InputBusinessBotRecipients (crc32 c4e5921e). */
final class TlInputBusinessBotRecipientsInputBusinessBotRecipients extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_business_bot_recipients_input_busine_36d9b3380d51';

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
        return $this->tlChild(TlInputBusinessBotRecipientsInputBusine36d9b3380d51Users::class);
    }
    public function excludeUsers(): HasMany
    {
        return $this->tlChild(TlInputBusinessBotRecipientsInputBusine36d9b3380d51Exclude_users::class);
    }
}
