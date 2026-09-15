<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * tf_users_emoji_status — 1:1 object child of TfUser (user.emoji_status,
 * flags.30?EmojiStatus). EmojiStatus union (emojiStatusEmpty / emojiStatus /
 * emojiStatusCollectible / inputEmojiStatusCollectible). Internal colors /
 * dark_colors vectors are deferred to the flat write path.
 *
 * @property int $account_id
 * @property int $id
 * @property string $constructor
 * @property int $document_id
 * @property int $until
 * @property int $collectible_id
 * @property string $title
 * @property string $slug
 * @property int $pattern_document_id
 * @property int $center_color
 * @property int $edge_color
 * @property int $pattern_color
 * @property int $text_color
 */
final class TfUsersEmojiStatus extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_users_emoji_status';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'document_id' => 'integer',
        'until' => 'integer',
        'collectible_id' => 'integer',
        'pattern_document_id' => 'integer',
        'center_color' => 'integer',
        'edge_color' => 'integer',
        'pattern_color' => 'integer',
        'text_color' => 'integer',
    ];

    /**
     * @return array<string, int>
     */
    public function childKey(): array
    {
        return [
            'account_id' => (int) $this->account_id,
            'id' => (int) $this->id,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(TfUser::class, 'id', 'id');
    }
}
