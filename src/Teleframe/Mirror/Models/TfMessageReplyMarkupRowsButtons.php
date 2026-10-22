<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * tf_messages_reply_markup_rows_buttons — 1:N positioned child of
 * tf_messages_reply_markup_rows (reply_markup.rows[].buttons,
 * Vector<KeyboardButton>). Its real parent is the ROW, not TfMessage: a
 * button hangs under the row named by `parent_position`, while its own
 * `position` is only the button's index WITHIN that row. The parent side of
 * this link is TfMessageReplyMarkupRows::buttons().
 *
 * Real PK is composite (account_id, id, position, parent_position): `id` is
 * NOT unique — two buttons of one row may share a position, and an ordinary
 * one-button-per-row keyboard emits every lone button at position 0 — so `id`
 * is a best-effort scalar stand-in (Eloquent has ONE key slot; declaring
 * (account_id, id, position) as it once did made MirrorFactWriter's
 * updateOrInsert overwrite button #1 with button #2 for the same row, see the
 * migration comment in 2026_09_14_200010_create_tf_messages_tables.php). Every
 * query here is account-scoped and position-aware, never ->find() across
 * accounts; childKey() carries the full four-column identity.
 *
 * There is deliberately NO belongsTo to the row: with one key slot the only
 * single-column pair is (parent_position → position), which is ambiguous
 * across the rows of one message and would resolve to whichever row came
 * first. Read the relation from the parent instead.
 *
 * @property int $account_id
 * @property int $id
 * @property int $position
 * @property int $parent_position
 * @property string $constructor
 * @property bool $requires_password
 * @property bool $same_peer
 * @property bool $quiz
 * @property string $text
 * @property string $url
 * @property string $data
 * @property string $query
 * @property string $fwd_text
 * @property int $button_id
 * @property int $user_id
 * @property int $max_quantity
 * @property string $copy_text
 */
final class TfMessageReplyMarkupRowsButtons extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_messages_reply_markup_rows_buttons';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'position' => 'integer',
        'parent_position' => 'integer',
        'requires_password' => 'boolean',
        'same_peer' => 'boolean',
        'quiz' => 'boolean',
        'button_id' => 'integer',
        'user_id' => 'integer',
        'max_quantity' => 'integer',
    ];

    /**
     * @return array<string, int>
     */
    public function childKey(): array
    {
        return [
            'account_id' => (int) $this->account_id,
            'id' => (int) $this->id,
            'position' => (int) $this->position,
            'parent_position' => (int) $this->parent_position,
        ];
    }
}
