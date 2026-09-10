<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessBotRecipientsBusinessBotRecipientsExclude_users;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessBotRecipientsBusinessBotRecipientsUsers;

/** Constructor model for businessBotRecipients of BusinessBotRecipients (crc32 b88cf373). */
final class TlBusinessBotRecipientsBusinessBotRecipients extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_business_bot_recipients_business_bot_recipients';

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
        return $this->tlChild(TlBusinessBotRecipientsBusinessBotRecipientsUsers::class);
    }
    public function excludeUsers(): HasMany
    {
        return $this->tlChild(TlBusinessBotRecipientsBusinessBotRecipientsExclude_users::class);
    }
}
