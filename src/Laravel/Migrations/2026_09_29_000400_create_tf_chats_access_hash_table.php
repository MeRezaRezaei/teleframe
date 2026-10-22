<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NF5 mirror — `tf_chats.access_hash` as its own 1:1 child table.
 *
 * WHY A TABLE AND NOT A COLUMN. `Chat` (TL 48) is a five-constructor union —
 * chatEmpty / chat / chatForbidden / channel / channelForbidden — and the
 * operator's NF5 design spec
 * (`docs/superpowers/specs/2026-09-11-telegram-mirror-schema-nf5-design.md:56`
 * and `:384`) puts all five on `tf_chats`. `access_hash` is NOT one of those
 * five's shared facts: `chatEmpty`, `chat` and `chatForbidden` do not carry
 * it on the wire at all, only `channel` / `channelForbidden` do. Every other
 * flag-gated union fact in this schema already lands as a 1:1 child table
 * keyed `(account_id, id)` with row existence = fact existence, and
 * `access_hash` is exactly that shape. The `Chat` base row therefore stays
 * the five-ctor common denominator and this table carries the flag-gated
 * fact — mirroring `tf_users_access_hash`
 * (`2026_09_14_200001_create_tf_users_tables.php:104-107`) exactly.
 *
 * WHY `channel` MATTERS. `mirrorPeerLookup()`
 * (`src/Laravel/Console/BackfillCommand.php:322-334`) resolves a negative peer
 * id — a channel — against the access-hash store before it will dial. Until
 * this table existed the only catalogued home for a channel's hash was
 * `tf_chats`' FK children (`migrated_to` / `admin_rights` /
 * `default_banned_rights`), none of which is a hash, so every `--peer=<channel>`
 * was refused with `peer_without_access_hash` even after a real `--discover`
 * had stored 157 chats. The `access_hash` column at
 * `2026_09_14_200002:89` is on `tf_chats_migrated_to` — a different table, an
 * `FK→InputChannel` reference, not a hash store.
 *
 * `tf_channels` is NOT a catalog table — it appears nowhere in the NF5 spec
 * and nothing in `src/` writes it. It is a leftover from generated migrations
 * the operator purged. Reading it was the actual bug; this table is the real
 * fix, not a rename of a phantom.
 *
 * COLUMN TYPES, exactly as `tf_users_access_hash` types them:
 *   - `account_id` unsignedBigInteger, `id` bigInteger — the parent key, and
 *     signed because channel/chat ids go negative (see the `tf_users` header
 *     comment: the peer target tables deliberately do not restrict sign);
 *   - `access_hash` bigInteger SIGNED. This is a TL `long`, not `bytes`:
 *     measured over 200 000 random TL `long`s decoded with `unpack('P')`,
 *     50.0% came out negative, so the signed 64-bit column is the correct
 *     type. Storing it as hex/TEXT would be wrong on both counts — every
 *     lookup would degrade into a string compare and the index would stop
 *     matching the way `tf_users_access_hash` already matches.
 *
 * NO FK constraints — cross-domain wiring is the Task-8 migration
 * (`2026_09_14_299999_create_tf_foreign_keys.php`), same as every other
 * hand-authored mirror domain migration here.
 *
 * Provenance: `src/Schema/schema/mirror-catalog.json` (`tf_chats` children:
 * `access_hash`), and the NF5 spec above.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_chats_access_hash', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->bigInteger('access_hash');
            $table->primary(['account_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_chats_access_hash');
    }
};
