<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * tf_messages_reply_markup_rows — 1:N positioned child of TfMessage
 * (reply_markup.rows, Vector<KeyboardButtonRow>). Each row is one positioned
 * row; the buttons under each row live in the separate
 * tf_messages_reply_markup_rows_buttons table, modelled as
 * TfMessageReplyMarkupRowsButtons and reachable through buttons().
 *
 * @property int $account_id
 * @property int $id
 * @property int $position
 * @property string $constructor
 */
final class TfMessageReplyMarkupRows extends TfChildModel
{
    use PinsDeclaredForeignKeyLegs;

    use ResolvesMessagePeerScope;

    use AccountScoped;

    protected $table = 'tf_messages_reply_markup_rows';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'position' => 'integer',
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
        ];
    }

    /**
     * The ENCLOSING MESSAGE, joined on the message's FULL peer-scoped key.
     *
     * NOT `('id','id')` any more. Telegram numbers messages PER CHAT — 136 of
     * one real account's dialogs hold a message with id=1 — so `id` is unique
     * only WITHIN (account_id, peer_type, peer_id). A belongsTo on `id` alone
     * resolved to whichever dialog happened to be written last, which is the
     * same cross-dialog collapse the primary key was widened to end, one level
     * up in the ORM. `account_id` is still supplied by the AccountScope global
     * scope, exactly as before.
     */
    public function message(): BelongsTo
    {
        return $this->pinPeerScopeBelongsTo($this->belongsTo(TfMessage::class, 'id', 'id'));
    }

    /**
     * The buttons of THIS row. `parent_position` is the column that says which
     * row a button hangs under — it is matched against this row's `position`;
     * a button's own `position` is only its index inside the row and must
     * never be joined here.
     *
     * `position` is not unique within a message, so the WHOLE message key is
     * pinned as well; account_id comes from the AccountScope global scope.
     * Without those legs this relation would also return the buttons of
     * another message's row at the same position — the same silent cross-row
     * collision the composite primary key was widened to kill.
     *
     * The peer legs are new for the same reason one rung up: a button is a fact
     * about a message, and message ids are per-chat, so `id` alone does not
     * name a message. The relation therefore mirrors the FK exactly —
     * (account_id, peer_type, peer_id, id, parent_position) → (account_id,
     * peer_type, peer_id, id, position) — which is what
     * `RelationIntegrityTest::test_model_relations_resolve_to_the_fk_pairs`
     * asserts the two cannot drift apart over.
     */
    public function buttons(): HasMany
    {
        $buttons = $this->hasMany(
            TfMessageReplyMarkupRowsButtons::class,
            'parent_position',
            'position',
        );

        foreach (TfMessage::MESSAGE_KEY as $leg) {
            $buttons->where($leg, $this->getAttribute($leg));
        }

        return $buttons;
    }
}
