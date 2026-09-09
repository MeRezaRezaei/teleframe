<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessRecipients;

/** Constructor model for businessGreetingMessage of BusinessGreetingMessage (crc32 e519abab). */
final class TlBusinessGreetingMessageBusinessGreetingMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_business_greeting_message_business_greeting_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'shortcut_id' => 'int',
        'no_activity_days' => 'int',
    ];

    public function recipients(): BelongsTo
    {
        return $this->belongsTo(TlBusinessRecipients::class, 'recipients');
    }
}
