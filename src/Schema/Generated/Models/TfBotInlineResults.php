<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_bot_inline_results`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 6.
 */
final class TfBotInlineResults extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_bot_inline_results';

    protected $guarded = [];

    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */
    public $incrementing = false;

    /**
     * Eloquent gets ONE scalar key. The real key is the composite (account_id, id),
     * which the database enforces; `id` is total only WITHIN an account, and
     * AccountScope guarantees that. So `find()` is account-scoped by construction:
     * reach for a relation or an explicit where, never a bare find().
     */
    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * Derived from the type the ENGINE reports for each column — never from a
     * docblock, and never from SQLite, whose affinity collapses integer(),
     * bigInteger() and tinyInteger() alike and drops width and signedness.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'account_id' => 'int',
        'constructor' => 'string',
        'id' => 'string',
        'type' => 'string',
    ];

    /** Declared foreign key (1:N): `tf_bot_inline_results_content`(account_id, id) -> `tf_bot_inline_results`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function botInlineResultsContent(): HasMany
    {
        return $this->hasMany(TfBotInlineResultsContent::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_bot_inline_results_description`(account_id, id) -> `tf_bot_inline_results`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function botInlineResultsDescription(): HasMany
    {
        return $this->hasMany(TfBotInlineResultsDescription::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_bot_inline_results_send_message`(account_id, id) -> `tf_bot_inline_results`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function botInlineResultsSendMessage(): HasMany
    {
        return $this->hasMany(TfBotInlineResultsSendMessage::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_bot_inline_results_thumb`(account_id, id) -> `tf_bot_inline_results`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function botInlineResultsThumb(): HasMany
    {
        return $this->hasMany(TfBotInlineResultsThumb::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_bot_inline_results_title`(account_id, id) -> `tf_bot_inline_results`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function botInlineResultsTitle(): HasMany
    {
        return $this->hasMany(TfBotInlineResultsTitle::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_bot_inline_results_url`(account_id, id) -> `tf_bot_inline_results`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function botInlineResultsUrl(): HasMany
    {
        return $this->hasMany(TfBotInlineResultsUrl::class, 'id', 'id');
    }
}
