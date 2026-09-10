<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateDeleteScheduledMessagesMessages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateDeleteScheduledMessagesSent_messages;

/** Constructor model for updateDeleteScheduledMessages of Update (crc32 f2a71983). */
final class TlUpdateUpdateDeleteScheduledMessages extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_update_update_delete_scheduled_messages';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
    ];

    public function messages(): HasMany
    {
        return $this->tlChild(TlUpdateUpdateDeleteScheduledMessagesMessages::class);
    }
    public function sentMessages(): HasMany
    {
        return $this->tlChild(TlUpdateUpdateDeleteScheduledMessagesSent_messages::class);
    }
}
