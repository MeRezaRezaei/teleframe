<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialog;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesChannelDifferenceChannelDifferenceTooLongChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesChannelDifferenceChannelDifferenceTooLongMessages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesChannelDifferenceChannelDifferenceTooLongUsers;

/** Constructor model for updates.channelDifferenceTooLong of updates.ChannelDifference (crc32 a4bcc6fe). */
final class TlUpdatesChannelDifferenceChannelDifferenceTooLong extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_updates_channel_difference_channel_difference_too_long';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'final' => 'bool',
        'timeout' => 'int',
    ];

    public function messages(): HasMany
    {
        return $this->tlChild(TlUpdatesChannelDifferenceChannelDifferenceTooLongMessages::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlUpdatesChannelDifferenceChannelDifferenceTooLongChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlUpdatesChannelDifferenceChannelDifferenceTooLongUsers::class);
    }

    public function dialog(): BelongsTo
    {
        return $this->belongsTo(TlDialog::class, 'dialog');
    }
}
