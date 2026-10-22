<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NF5 mirror — `tf_messages_rich_message`, the one direct mirror child of a
 * catalog parent that the shipped dial never created.
 *
 * `mirror-catalog.json` declares `rich_message` on `tf_messages` as a child
 * fact (`["rich_message", "FK→RichMessage"]`). `MirrorTableResolver` materialises
 * that entry as a child table NAMED `{parent}_{field}`, and
 * `MirrorFactDecomposer::decomposeChildren()` walks every direct child of a
 * resolved parent, lifting `$payload['rich_message']` straight off the wire.
 * `message#7600b9d3` carries `rich_message:flags2.13?RichMessage`
 * (schema/sources/TL_telegram_v227.tl:121), and `message` is a top-level Update
 * constructor — `updateNewMessage`, `updateMessageID`, `updateEditMessage`, …
 * So the row was built and handed to `MirrorFactWriter`, which inserts with no
 * existence check, and the insert died: `no such table: tf_messages_rich_message`.
 *
 * Why it stayed hidden so long: the field is flags2.13-gated, so `rich_message`
 * is ABSENT from virtually every message, and row existence = fact existence
 * means nothing was ever written until a rich message actually arrived. A clean
 * `php artisan migrate` returned green throughout.
 *
 * `MirrorFactWriter` deliberately does not absorb this. Its column-listing
 * pre-filter treats an EMPTY listing as "the table itself is missing" and lets
 * the insert raise, because a missing fact TABLE is a categorically bigger
 * defect than an unmodelled field. That line is right and is what made this
 * loud instead of silent — which is precisely why the fix has to be a real
 * migration and not a writer-side swallow.
 *
 * ## Columns, read out of the writer, not off the TL line
 *
 * The row is the one `MirrorFactDecomposer::decompose()` actually emits for a
 * `message` carrying a `richMessage` — measured, not assumed:
 *
 *     {"account_id":7,"id":4242,"rtl":1,"part":0}
 *
 * `account_id` is the scoping key; `id` is the PARENT key travelling into the
 * child as its FK back to `tf_messages`; `rtl` and `part` are boolean columns,
 * which `childRow()` writes unconditionally as `(int) (bool)` so a payload
 * omitting them still places. `richMessage` is single-ctor, so there is no
 * `constructor` discriminator. Types follow the curated dial for its siblings
 * (`tf_messages_factcheck`, `tf_messages_suggested_post`, and the FK'd
 * `tf_messages_media`): `integer('id')` to match the `tf_messages` primary key
 * the FK is destined for, `unsignedBigInteger('account_id')` per MySQL-3780,
 * booleans type-defaulted.
 *
 * No foreign key is declared here. The messages migration states the rule and
 * the gap-tables migration follows it: cross-domain wiring (this child's
 * `cascadeOnDelete` to `tf_messages`) belongs in the 299999 FK migration, and
 * that file's DDL is never edited. This table's absence from it is the same
 * open wiring gap the other message children carry today.
 *
 * NOT created here, on purpose: `tf_messages_rich_message_blocks`,
 * `_photos` and `_documents`. They resolve in the same DDL graph, but
 * `decomposeChildren()` walks only the DIRECT children of a resolved parent and
 * `childRow()` does not recurse, so the ingest path never builds a row for a
 * grandchild. Creating them now would be speculative DDL for a write path that
 * does not exist; when the writer does recurse, the graph-level assertion in
 * `MirrorCatalogShipsInMigrationsTest` will name them.
 *
 * Provenance: src/Schema/schema/mirror-catalog.json (tf_messages children);
 * src/Schema/Mirror/MirrorTableResolver.php:255; src/Schema/Mirror/
 * MirrorFactDecomposer.php:169 and :214; src/Schema/Mirror/MirrorFactWriter.php:116;
 * schema/sources/TL_telegram_v227.tl:121.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_messages_rich_message', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. Telegram numbers
            // messages PER CHAT, so `id` alone is not an identity across a real
            // account's dialogs, and this row is a fact ABOUT one message — so
            // its key must be at least as specific as its parent's
            // (account_id, peer_type, peer_id, id). NOT NULL with no default on
            // purpose: a zero default would land the row at (account_id, 0, 0,
            // id) instead of failing loudly, which is the silent 0/0 corruption
            // this widening exists to end. See
            // 2026_09_14_200010_create_tf_messages_tables.php.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->boolean('rtl')->default(false);
            $table->boolean('part')->default(false);

            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_messages_rich_message');
    }
};
