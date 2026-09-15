<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 child of tf_messages_service (messageService.action payload, MessageAction
 * union, 67 ctors), constructor-discriminated flattened union.
 */
final class TfMessageAction extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_messages_service_action';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'recurring_init' => 'boolean',
        'recurring_used' => 'boolean',
        'attach_menu' => 'boolean',
        'from_request' => 'boolean',
        'video' => 'boolean',
        'title_missing' => 'boolean',
        'same' => 'boolean',
        'for_both' => 'boolean',
        'via_giveaway' => 'boolean',
        'unclaimed' => 'boolean',
        'name_hidden' => 'boolean',
        'saved' => 'boolean',
        'converted' => 'boolean',
        'upgraded' => 'boolean',
        'refunded' => 'boolean',
        'can_upgrade' => 'boolean',
        'prepaid_upgrade' => 'boolean',
        'upgrade_separate' => 'boolean',
        'auction_acquired' => 'boolean',
        'transferred' => 'boolean',
        'assigned' => 'boolean',
        'from_offer' => 'boolean',
        'craft' => 'boolean',
        'closed' => 'boolean',
        'hidden' => 'boolean',
        'rejected' => 'boolean',
        'balance_too_low' => 'boolean',
        'payer_initiated' => 'boolean',
        'expired' => 'boolean',
        'missed' => 'boolean',
        'active' => 'boolean',
        'broadcast_messages_allowed' => 'boolean',
        'accepted' => 'boolean',
        'declined' => 'boolean',
        'prev_value' => 'boolean',
        'new_value' => 'boolean',
        'user_id' => 'integer',
        'inviter_id' => 'integer',
        'channel_id' => 'integer',
        'chat_id' => 'integer',
        'game_id' => 'integer',
        'score' => 'integer',
        'total_amount' => 'integer',
        'subscription_until_date' => 'integer',
        'call_id' => 'integer',
        'duration' => 'integer',
        'from_id_type' => 'integer',
        'from_id_id' => 'integer',
        'to_id_type' => 'integer',
        'to_id_id' => 'integer',
        'distance' => 'integer',
        'period' => 'integer',
        'auto_setting_from' => 'integer',
        'schedule_date' => 'integer',
        'days' => 'integer',
        'crypto_amount' => 'integer',
        'icon_color' => 'integer',
        'icon_emoji_id' => 'integer',
        'button_id' => 'integer',
        'stars' => 'integer',
        'boost_peer_type' => 'integer',
        'boost_peer_id' => 'integer',
        'winners_count' => 'integer',
        'unclaimed_count' => 'integer',
        'boosts' => 'integer',
        'convert_stars' => 'integer',
        'upgrade_msg_id' => 'integer',
        'upgrade_stars' => 'integer',
        'gift_msg_id' => 'integer',
        'gift_num' => 'integer',
        'can_export_at' => 'integer',
        'transfer_stars' => 'integer',
        'can_transfer_at' => 'integer',
        'can_resell_at' => 'integer',
        'drop_original_details_stars' => 'integer',
        'can_craft_at' => 'integer',
        'count' => 'integer',
        'expires_at' => 'integer',
        'new_creator_id' => 'integer',
        'bot_id' => 'integer',
        'months' => 'integer',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(TfMessageService::class, 'id', 'id');
    }
}
