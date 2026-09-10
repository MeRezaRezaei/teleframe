<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionCreateTopic;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionDeleteTopic;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionEditTopic;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionPinTopic;

/** Anchor model for TL type ForumTopic (spec §4.1). */
final class TlForumTopic extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_forum_topic_forum_topic';

    protected $guarded = [];

    public function newTopic(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionEditTopic::class, 'new_topic');
    }
    public function newTopicChannelAdminLogEventActionPinTopic(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionPinTopic::class, 'new_topic');
    }
    public function prevTopic(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionEditTopic::class, 'prev_topic');
    }
    public function prevTopicChannelAdminLogEventActionPinTopic(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionPinTopic::class, 'prev_topic');
    }
    public function topic(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionCreateTopic::class, 'topic');
    }
    public function topicChannelAdminLogEventActionDeleteTopic(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionDeleteTopic::class, 'topic');
    }
}
