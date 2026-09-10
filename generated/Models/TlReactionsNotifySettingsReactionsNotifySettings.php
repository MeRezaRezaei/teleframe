<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBool;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlNotificationSound;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReactionNotificationsFrom;

/** Constructor model for reactionsNotifySettings of ReactionsNotifySettings (crc32 71e4ea58). */
final class TlReactionsNotifySettingsReactionsNotifySettings extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_reactions_notify_settings_reactions_notify_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
    ];

    public function messagesNotifyFrom(): BelongsTo
    {
        return $this->belongsTo(TlReactionNotificationsFrom::class, 'messages_notify_from');
    }
    public function storiesNotifyFrom(): BelongsTo
    {
        return $this->belongsTo(TlReactionNotificationsFrom::class, 'stories_notify_from');
    }
    public function pollVotesNotifyFrom(): BelongsTo
    {
        return $this->belongsTo(TlReactionNotificationsFrom::class, 'poll_votes_notify_from');
    }
    public function sound(): BelongsTo
    {
        return $this->belongsTo(TlNotificationSound::class, 'sound');
    }
    public function showPreviews(): BelongsTo
    {
        return $this->belongsTo(TlBool::class, 'show_previews');
    }
}
