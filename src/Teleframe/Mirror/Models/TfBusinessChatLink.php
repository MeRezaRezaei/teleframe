<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * NF5 mirror of the businessChatLink ctor — table tf_business_chat_links.
 *
 * The Telegram-supplied link string IS the key (catalog `base` leads with
 * `link`); every child is keyed (account_id, link). message is the required
 * TextWithEntities flattened to its text; entities are flattened as the 1:N
 * tf_business_chat_links_entities child using the messages-domain entity set.
 *
 * @property int $account_id
 * @property string $link
 * @property string $message
 * @property int $views
 */
final class TfBusinessChatLink extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_business_chat_links';

    protected $primaryKey = 'link';

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'int',
        'views' => 'int',
    ];

    /** 1:N — the Vector<MessageEntity> of the link text; caller orders by slot. */
    public function entities(): HasMany
    {
        return $this->hasMany(TfBusinessChatLinkEntity::class, 'link', 'link');
    }

    public function title(): HasOne
    {
        return $this->hasOne(TfBusinessChatLinkTitle::class, 'link', 'link');
    }

    /**
     * The full composite key of this row, for writing a child fact.
     *
     * @return array<string, int|string>
     */
    public function childKey(): array
    {
        return [
            'account_id' => (int) $this->account_id,
            'link' => (string) $this->link,
        ];
    }
}
