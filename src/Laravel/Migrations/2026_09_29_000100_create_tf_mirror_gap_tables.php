<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * NF5 mirror — the catalog tables no shipped migration created.
 *
 * `src/Schema/schema/mirror-catalog.json` declares 35 top-level tables. Until
 * this migration, 27 of them had a `Schema::create` in the shipped set and
 * nine did not: tf_dialog_filters, tf_forum_topics, tf_message_actions,
 * tf_message_entities, tf_message_medias, tf_reactions,
 * tf_saved_reaction_tags, tf_story_items, tf_wallpapers.
 *
 * ## `tf_message_entities` WAS THE NINTH, AND IS NOW RETIRED — read this first
 *
 * It was created here on 2026-09-29 and DELETED from here on 2026-10-04,
 * together with its `mirror-catalog.json` entry. Nothing was lost: entities are
 * stored, correctly and without loss, in the sibling `tf_messages_entities`
 * (`2026_09_14_200011_create_tf_message_entities_tables.php`), a positioned
 * child of `tf_messages` keyed (account_id, peer_type, peer_id, id, position).
 *
 * MEASURED 2026-10-04 over ten payload shapes (`message` with and without
 * entities, `messageService`, fwd/reply + entities, `updateNewMessage`,
 * `updateEditMessage`, `updateMessageID`, a two-message page, a `dialog`, a
 * `webPage`): the shipped `MirrorCatalog` / `MirrorFactDecomposer` emitted 0
 * rows into `tf_message_entities` and 18 into `tf_messages_entities`, and the
 * three entities of one message were stored at position 0, 1, 2 with `offset`
 * and `length` intact. The dead table also had 0 declared nested sources — no
 * edge reached it — yet `MirrorCatalog::tableForCtor('messageEntityBold')`
 * still resolved to it, which is exactly what made it LOOK reachable.
 *
 * Why deletion and not repair. The shipped key was
 * `primary(['account_id','message_entity_id'])` and no `MessageEntity`
 * constructor carries an id of its own, so every entity in one message would
 * have collapsed onto one row. Repair was attempted and CORRECTLY REFUSED:
 * declaring `scoped_by: ["peer_type"]` raises `MirrorSchemaException:
 * scoped_by 'peer_type' is neither a base column nor a parameter of any
 * constructor of MessageEntity`, and the correct key — (message identity,
 * position) — is unattainable here because `position` is minted only for vector
 * children, which requires a `children` entry, and a context-free
 * `MessageEntity` table has no list to be a child of. That correct shape
 * already ships, as `tf_messages_entities`.
 *
 * FORCING rows through the retired key is not a loud total drop either — it is
 * the quiet kind. With the key covered, three hand-built entity rows returned
 * `inserted=3`, all four clue buckets EMPTY, and the table held ONE row: two
 * facts destroyed with no error, no clue and nothing to retry on. (A row that
 * omits `message_entity_id` does raise an `fkClues` entry instead; that is the
 * loud shape, and it is not the one ordinary traffic would produce, because
 * ordinary traffic produces no rows here at all.)
 *
 * So this migration now closes EIGHT of the nine gaps it was written for, and
 * this note is the record of the ninth.
 *
 * Why the other eight were a production outage and not a cosmetic gap:
 * `MirrorIngesterFactory`
 * resolves a decoded TL constructor to a table name through
 * `MirrorCatalog::tableForCtor()` and inserts with NO existence check, and
 * `MirrorTableResolver` resolves that name to a DDL graph whose column names and
 * primary key are the contract the write path honours. A clean `php artisan migrate`
 * therefore returned green, and the FIRST live update carrying a Reaction,
 * ForumTopic, StoryItem, Wallpaper, MessageMedia, MessageAction, DialogFilter
 * or SavedReactionTag row died on insert with a no-such-table error — on a host
 * that had been told the install succeeded. There was no migrate-time signal and
 * no startup-time signal, only the first write.
 *
 * (MessageEntity was in that list too, and it never actually reached this
 * failure: see the retirement note above. Its facts land in `tf_messages_entities`,
 * which `2026_09_14_200011` already shipped, so the no-such-table write it was
 * created to prevent could not occur.)
 *
 * These are NOT the singular/plural twins of the message-scoped children
 * that already ship. The catalog parents are context-free and keyed on a
 * synthetic id (message_media_id, message_action_id,
 * reaction_id, saved_reaction_tag_id) because their own first base column is a
 * fact, not a Telegram native id; `tf_messages_entities` / `tf_messages_media` /
 * `tf_messages_service_action` are positioned children of `tf_messages` keyed
 * (account_id, id, position). They are different tables with different keys, so
 * the gap is closed by CREATING the catalog tables — never by renaming one into
 * the other, which would silently repoint the writer at the wrong primary key.
 *
 * Shape contract, identical to the rest of the curated dial: one row per
 * (account_id, <key>), `constructor` as the ctor-name discriminator on every
 * multi-ctor parent, every non-key column type-defaulted so a partial
 * decomposer write (which emits only the columns a given ctor actually carries)
 * still places, and the positioned children carry `position` in their PK.
 * Child tables mirror the catalog's fact decomposition: a scalar/peer FK→
 * reference that resolves to a catalog parent is a row in THAT parent's table,
 * not a table of its own, so only the synthetic-key children are created here.
 *
 * ## WHY `tf_message_medias` IS KEYED (account_id, peer_type, peer_id, message_media_id)
 *
 * MEASURED 2026-10-04 on the operator's live account: 7 of the 10 discarded
 * `messages.getDialogs` pages died on `Duplicate entry
 * '1724372757-958882' for key 'tf_message_medias.PRIMARY'`, with the insert
 * carrying `message_media_id` — the value the enclosing message's `id:int`
 * supplies through the `sources.context` overlay.
 *
 * `message#7600b9d3` (`src/Schema/schema/sources/api_full.tl:121`) carries
 * `id:int` AND `peer_id:Peer`, and Telegram numbers `id` PER CHAT, so
 * `(account_id, message_media_id)` was never an identity: two dialogs holding
 * the same message number collide on the account's own primary key. The sibling
 * tables already say so in their DDL — `tf_messages` and its 36 children key
 * `(account_id, peer_type, peer_id, id)`
 * (`2026_09_14_200010_create_tf_messages_tables.php:190-211`) and
 * `tf_messages_media` carries `message_peer_type`/`message_peer_id`
 * (`2026_09_14_200012_create_tf_message_medias_tables.php:28-35`). This table
 * was the one media store left keyed inside a scope it never declared, so the
 * peer scope is added here and in the catalog (`scoped_by`) together.
 *
 * `message_media_id` STAYS, and it is not a fabricated column. `MessageMedia` is
 * a pure union of 19 constructors (`api_full.tl:124-142`) and a message carries
 * at most one `media` (`message#7600b9d3 … media:flags.9?MessageMedia`,
 * `api_full.tl:121`), so the enclosing message's id is what identifies the media
 * row. What was missing was the chat that number is scoped to.
 *
 * CORRECTED 2026-10-04: this header previously justified that by claiming the 19
 * constructors "carry NO `id` parameter between them". That was FALSE, and the
 * range it cited refuted it in the same file. Exactly one DOES carry a bare
 * `id` — `messageMediaStory#68cb6283 … peer:Peer id:int story:flags.0?StoryItem`
 * (`api_full.tl:137`) — and it is not a counter-example to the conclusion, it is
 * the sharper reason for it: that `id` is a STORY id, in the stories namespace,
 * and it is present on 1 of 19 constructors, so it cannot identify the media row
 * either. The shipped DDL had already reached that verdict on its own and
 * DROPPED `messageMediaStory.id` as a plain-name collision with the parent FK
 * column `id`
 * (`2026_09_14_200012_create_tf_message_medias_tables.php:18-19`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_reactions', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('reaction_id');
            $table->text('constructor');
            $table->text('emoticon')->default(DB::raw("('')"));

            $table->primary(['account_id', 'reaction_id']);
        });

        Schema::create('tf_forum_topics', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->text('constructor');

            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_story_items', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->text('constructor');

            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_wallpapers', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->text('constructor');
            $table->bigInteger('access_hash')->default(0);
            $table->text('slug')->default(DB::raw("('')"));
            $table->boolean('creator')->default(false);
            $table->boolean('default')->default(false);
            $table->boolean('pattern')->default(false);
            $table->boolean('dark')->default(false);

            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_wallpapers_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->boolean('blur')->default(false);
            $table->boolean('motion')->default(false);
            $table->integer('background_color')->default(0);
            $table->integer('second_background_color')->default(0);
            $table->integer('third_background_color')->default(0);
            $table->integer('fourth_background_color')->default(0);
            $table->integer('intensity')->default(0);
            $table->integer('rotation')->default(0);
            $table->text('emoticon')->default(DB::raw("('')"));

            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_saved_reaction_tags', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('saved_reaction_tag_id');
            $table->integer('count')->default(0);

            $table->primary(['account_id', 'saved_reaction_tag_id']);
        });

        Schema::create('tf_saved_reaction_tags_title', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('saved_reaction_tag_id');
            $table->text('title')->default(DB::raw("('')"));

            $table->primary(['account_id', 'saved_reaction_tag_id']);
        });

        Schema::create('tf_message_medias', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            // Peer scope of the ENCLOSING MESSAGE — see the header note "WHY
            // tf_message_medias IS KEYED (account_id, peer_type, peer_id,
            // message_media_id)". `NOT NULL` and NO default, on purpose: a peer
            // half that cannot be filled must DROP the row loudly, never land on
            // a silent 0/0 (the `tf_dialogs.peer_type` hazard the mirror
            // resolver's own docblock records).
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->bigInteger('message_media_id');
            $table->text('constructor');
            $table->boolean('spoiler')->default(false);
            $table->boolean('live_photo')->default(false);

            $table->primary(['account_id', 'peer_type', 'peer_id', 'message_media_id']);
        });

        Schema::create('tf_message_medias_ttl_seconds', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            // `makeChildPkCols()` copies the PARENT's key onto every child, so
            // this table's PK widened in the same migration as its parent's —
            // a child keyed on less than its parent is a second, independent
            // duplicate-key surface, and the two halves are never separated.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->bigInteger('message_media_id');
            $table->integer('ttl_seconds')->default(0);

            $table->primary(['account_id', 'peer_type', 'peer_id', 'message_media_id']);
        });

        Schema::create('tf_message_actions', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->bigInteger('message_action_id');
            $table->text('constructor');
            $table->text('title')->default(DB::raw("('')"));

            $table->primary(['account_id', 'peer_type', 'peer_id', 'message_action_id']);
        });

        Schema::create('tf_message_actions_users', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->bigInteger('message_action_id');
            $table->integer('position');
            $table->bigInteger('value')->default(0);

            $table->primary(['account_id', 'peer_type', 'peer_id', 'message_action_id', 'position']);
        });

        Schema::create('tf_dialog_filters', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->text('constructor');
            $table->boolean('contacts')->default(false);
            $table->boolean('non_contacts')->default(false);
            $table->boolean('groups')->default(false);
            $table->boolean('broadcasts')->default(false);
            $table->boolean('bots')->default(false);
            $table->boolean('exclude_muted')->default(false);
            $table->boolean('exclude_read')->default(false);
            $table->boolean('exclude_archived')->default(false);
            $table->boolean('title_noanimate')->default(false);

            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_dialog_filters_title', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('text')->default(DB::raw("('')"));

            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_dialog_filters_title_entities', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->integer('position');
            $table->text('constructor');
            $table->integer('offset')->default(0);
            $table->integer('length')->default(0);
            $table->text('language')->default(DB::raw("('')"));
            $table->text('url')->default(DB::raw("('')"));
            $table->bigInteger('user_id')->default(0);
            $table->bigInteger('document_id')->default(0);
            $table->boolean('collapsed')->default(false);
            $table->boolean('relative')->default(false);
            $table->boolean('short_time')->default(false);
            $table->boolean('long_time')->default(false);
            $table->boolean('short_date')->default(false);
            $table->boolean('long_date')->default(false);
            $table->boolean('day_of_week')->default(false);
            $table->integer('date')->default(0);
            $table->text('old_text')->default(DB::raw("('')"));

            $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_dialog_filters_pinned_peers', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->integer('position');
            $table->text('constructor');
            $table->bigInteger('chat_id')->default(0);
            $table->bigInteger('user_id')->default(0);
            $table->bigInteger('access_hash')->default(0);
            $table->bigInteger('channel_id')->default(0);
            $table->integer('msg_id')->default(0);

            $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_dialog_filters_include_peers', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->integer('position');
            $table->text('constructor');
            $table->bigInteger('chat_id')->default(0);
            $table->bigInteger('user_id')->default(0);
            $table->bigInteger('access_hash')->default(0);
            $table->bigInteger('channel_id')->default(0);
            $table->integer('msg_id')->default(0);

            $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_dialog_filters_exclude_peers', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->integer('position');
            $table->text('constructor');
            $table->bigInteger('chat_id')->default(0);
            $table->bigInteger('user_id')->default(0);
            $table->bigInteger('access_hash')->default(0);
            $table->bigInteger('channel_id')->default(0);
            $table->integer('msg_id')->default(0);

            $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_dialog_filters_emoticon', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('emoticon')->default(DB::raw("('')"));

            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_dialog_filters_color', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->integer('color')->default(0);

            $table->primary(['account_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_dialog_filters_color');
        Schema::dropIfExists('tf_dialog_filters_emoticon');
        Schema::dropIfExists('tf_dialog_filters_exclude_peers');
        Schema::dropIfExists('tf_dialog_filters_include_peers');
        Schema::dropIfExists('tf_dialog_filters_pinned_peers');
        Schema::dropIfExists('tf_dialog_filters_title_entities');
        Schema::dropIfExists('tf_dialog_filters_title');
        Schema::dropIfExists('tf_dialog_filters');
        Schema::dropIfExists('tf_message_actions_users');
        Schema::dropIfExists('tf_message_actions');
        Schema::dropIfExists('tf_message_medias_ttl_seconds');
        Schema::dropIfExists('tf_message_medias');
        Schema::dropIfExists('tf_saved_reaction_tags_title');
        Schema::dropIfExists('tf_saved_reaction_tags');
        Schema::dropIfExists('tf_wallpapers_settings');
        Schema::dropIfExists('tf_wallpapers');
        Schema::dropIfExists('tf_story_items');
        Schema::dropIfExists('tf_forum_topics');
        Schema::dropIfExists('tf_reactions');
    }
};
