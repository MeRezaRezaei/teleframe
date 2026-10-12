<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TF-INV — the tf_message_search inverted index (one row per message token).
 *
 * Replaces the leading-wildcard LIKE in MessageSearchQuery. That query could
 * not be served by ANY index: a B-tree only helps a pattern with a known
 * prefix, so every search scanned the operator's entire message history — and
 * tf_messages is the largest table a real host owns, growing without bound.
 *
 * WHY A TOKEN TABLE, NOT FULLTEXT
 *
 * The three drivers this project ships do not share a full-text story, and the
 * differences are structural, not syntactic:
 *
 *   - MySQL  : FULLTEXT is an index TYPE on a column, queried with
 *              MATCH()...AGAINST(). Workable, but innodb_ft_min_token_size=3
 *              and the stopword list mean a short or common token silently
 *              matches nothing on one host and behaves differently on another.
 *   - Postgres: no FULLTEXT index exists. It requires a tsvector column, which
 *              needs either GENERATED ALWAYS AS ... STORED (no portable Laravel
 *              Blueprint spelling) or a plpgsql trigger function. Neither can
 *              be expressed here, and neither can be exercised by the SQLite
 *              suite that all the logic tests run on.
 *   - SQLite : needs an FTS5 virtual table — a different KIND of object, with
 *              its own DDL dialect and its own triggers.
 *
 * A trigger was considered and rejected for the same reason: triggers are the
 * one place the three dialects share nothing, so a trigger-populated index could
 * only ever be verified on one driver. Tokenization therefore happens in PHP
 * (MessageSearchIndexer::tokenize) and is byte-identical on all three, and the
 * table below is an ordinary table with an ordinary composite index that all
 * three read the same way.
 *
 * COLUMN ORDER IS THE SEARCH PATH
 *
 * The primary key is (account_id, token, peer_type, peer_id, id) — token
 * IMMEDIATELY after account_id, before the peer legs, and not in the natural
 * (account_id, id, token) order. This is the whole point of the table: a
 * search becomes an equality on account_id plus a RANGE over the token prefix,
 * and a clustered primary key serves that range by walking the index in order.
 * The intuitive ordering would make every search a full index scan, i.e. the
 * same defect one table over. `MessageSearchQuery` constrains NO peer ON THE
 * INDEX PATH — its tf_message_search subquery is account-wide, though search()
 * does apply ?peerId/?peerType to tf_messages — so the token range must stay
 * contiguous from the second column; the plan measurement is at primary() below.
 *
 * The uniqueness this buys is the same one the writer needs: index() is an
 * INSERT, not an upsert, so a repeated word in one message ("very very very")
 * would be a primary-key violation rather than a redundant row.
 *
 * (account_id, peer_type, peer_id, id) carries its own index
 * (tf_message_search_message_idx) because the writers address the PEER-SCOPED
 * message key — MessageSearchIndexer::index deletes through it and ::forget
 * deletes through it — and that four-column key is NOT a prefix of the primary
 * key. Without this index every message write and every message deletion would
 * scan the clustered key instead of seeking.
 *
 * MYSQL PORTABILITY (MySqlDialPortabilityTest)
 *
 *   - token is string('token', 255), NOT text: a TEXT column cannot be part of
 *     a MySQL index key at all (MySQL-1170), and 255 chars x utf8mb4 = 1020
 *     bytes keeps the composite key at ~1032 bytes, inside InnoDB's 3072-byte
 *     DYNAMIC-row limit.
 *   - account_id is unsignedBigInteger, matching telegram_accounts.id
 *     (MySQL-3780).
 *   - No ->foreign() is declared, so the auto-name 64-char rule (MySQL-1059)
 *     cannot trip. Referential integrity to tf_messages is deliberately left to
 *     the app layer: this is a derived index, and a FK here would make every
 *     message delete pay for a cascade across the largest table in the schema.
 *   - No TEXT column carries a bare literal default (MySQL-1101) — there are no
 *     TEXT columns at all.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_message_search', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->string('token', 255);
            $table->integer('id');

            // The peer scope of the message this token came from, and it is here
            // for the same reason it is on `tf_messages`: Telegram numbers
            // messages PER CHAT, so message id 5 exists in essentially every
            // dialog of a real account. An index keyed (account_id, id) made
            // `index()`'s delete-then-insert wipe every OTHER dialog's tokens for
            // that id and replace them with this one's — the same silent
            // overwrite the message table suffered, one derived structure down,
            // and it hit the SEARCH path rather than the storage path, so nothing
            // in the message mirror showed it.
            //
            // NOT NULL with no default, like every other message key column: an
            // index row that cannot name its message is not a degraded index row,
            // it is a wrong one. See
            // 2026_09_14_200010_create_tf_messages_tables.php.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');

            // NO created_at/updated_at. The curated dial is deliberately
            // timestamp-free (InjectableClockTest::test_curated_dial_is_
            // timestamp_free_and_clock_seamless asserts it for tf_messages and
            // tf_updates), because a mirror row records a Telegram fact, not the
            // moment this host happened to learn it — and a timestamp would
            // force a clock injection point into every writer for no meaning.
            // A search index row is even further from being a fact: it is derived
            // and rebuildable, so a timestamp on it would be misleading.

            // SEARCH path, and this is the PRIMARY KEY because it is the one the
            // query is shaped for: equality on `account_id`, then a RANGE over
            // the token prefix. The tf_message_search SUBQUERY does not constrain
            // the peer (search() applies ?peerId/?peerType to tf_messages instead), so
            // `token` must sit IMMEDIATELY after `account_id`: the range stays contiguous.
            //
            // Measured, and the reason the peer legs are NOT ahead of the token:
            // with the key (account_id, peer_type, peer_id, token, id) SQLite
            // plans `SEARCH tf_message_search USING COVERING INDEX
            // sqlite_autoindex_tf_message_search_1 (account_id=?)` plus
            // `USE TEMP B-TREE FOR ORDER BY` — the leading columns are equality
            // on account_id alone, so the token range is no longer a range at
            // all and every search sorts. That is the plan
            // `MessageSearchPlanTest::test_a_wider_index_would_not_change_the_plan`
            // asserts against.
            $table->primary(['account_id', 'token', 'peer_type', 'peer_id', 'id'], 'tf_message_search_pk');
            // WRITE/DELETE path: `index()` and `forget()` both address the WHOLE
            // peer-scoped message key, which is NOT a prefix of the primary key
            // — so this second index is load-bearing and is kept, exactly as the
            // old (account_id, id) index was. What changed is that it is
            // WIDENED rather than left narrow: keeping it at (account_id, id)
            // would have preserved the original bug, because `index()`'s
            // delete-then-insert addressed every dialog's tokens for one id.
            //
            // It was briefly DELETED rather than widened (2026-10-03, Task 2.2
            // fix round 1 — the migration edit that widened the primary key
            // dropped this index and nothing re-added it, leaving the comment
            // above describing an object the schema no longer had). Without it,
            // `MessageSearchIndexer::index()`'s delete and `forget()`'s delete —
            // both `WHERE account_id AND peer_type AND peer_id AND id` — had no
            // supporting index at all and full-scanned the largest derived table
            // on every message write and every delete. The four legs are the
            // peer-scoped message key the writers actually address, in exactly
            // that order, so the predicate is an index seek rather than a scan.
            $table->index(['account_id', 'peer_type', 'peer_id', 'id'], 'tf_message_search_message_idx');
        });

        // Existing hosts already hold millions of tf_messages rows that the
        // index has never seen. Backfilling is done in PHP (chunked, via
        // MessageSearchIndexer::reindexAccount) rather than in SQL, because
        // tokenization is a PHP function — INSERT..SELECT would need
        // driver-specific string functions and would tokenize differently on
        // each of the three drivers. See the class docblock for why that
        // matters more than the convenience.
        //
        // No data is moved here: a migration that rewrites the largest table in
        // the schema would hold a write lock for as long as the history is deep.
        // The backfill is a separate, resumable, out-of-band step.
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_message_search');
    }
};
