# Family extraction — messages/stories mirror family

- **Family:** messages / stories
- **Roots (9):** `tf_messages`, `tf_messages_service`, `tf_message_actions`, `tf_message_entities`, `tf_message_medias`, `tf_forum_topics`, `tf_story_items`, `tf_todo_items`, `tf_todo_lists`
- **TOTAL_TABLES:** 160 (resolved, incl. shared catalog nodes re-listed per referrer context); 113 distinct DDL tables
- **Date:** 2026-09-12
- **Audit:** `/tmp/audit_messages.php` — `RESULT=PASS`, exit 0
- **Fix commit:** `48aaee09` `feat(schema): mirror scalar catalog children and mark bytes TEXT as hex`

## NF5 verdict

PASS — 0 violations across all five audited invariants (whole family, all 160 resolved nodes).

| Invariant | Violations | Evidence |
| --- | --- | --- |
| I1 `account_id` first | 0 | every table: `columns[0]` = `account_id` (BIGINT) |
| I2 no nullable / no json / no blob / no binary; bytes = hex TEXT | 0 | catalog shapes and resolved columns scanned for `NULLABLE/JSON/BLOB/BINARY`; 21 hex columns carry `hex=true` on `STRING` (`waveform`, `poll_option`, …) |
| I3 PK = `(account_id, keyColumns[, position])` | 0 | writer emits exactly `primary(['account_id', ...keyColumns, 'position'?])`; all 160 tables match |
| I4 no duplicate sibling table names | 0 | per-parent `tfName` scan unique (resolver `hasChild` dedupe) |
| I5 peer shapes = `{f}_type` + `{f}_id` pair | 0 | 50 peer columns, every pair complete |

Supporting counts: 134 child tables emit the CASCADE parent FK (`parentTf !== ''` → `ON DELETE cascade` at write time); 0 RESTRICT reference FKs (see §14 delta note below). Peer evidence incl. `tf_messages.peer_id`, `tf_messages.from_id`, `tf_messages.service saved_peer_id/guestchat_via_from`.

### Splitter proof (`tf_messages_service`)

Derived from the `messageService` wire ctor with no catalog edit:

- bools (7, ctor param order): `out, mentioned, media_unread, reactions_are_possible, silent, post, legacy` — `reactions_are_possible` is service-only, absent from the merged catalog bools.
- children: `from_id, saved_peer_id, reply_to, reactions, ttl_period` (parent∩ctor) + appended ref `action → FK→MessageAction`; child `tf_message_actions` carries the `constructor` discriminator (67 ctors).
- base (parent∩ctor): `id, peer_id, date`; `message` correctly excluded (not in `messageService`).

## Per-root tables

### `tf_messages` (tl `Message`; 103 tables in subtree; root + 102 children)
- PK `(account_id, id)`; discriminator `constructor` (ctors: `messageEmpty`, `message`).
- Columns: `account_id, id, constructor, peer_id_type, peer_id_id, date, message` + 15 booleans (`out, mentioned, media_unread, silent, post, from_scheduled, legacy, edit_hide, pinned, noforwards, invert_media, offline, video_processing_pending, paid_suggested_post_stars, paid_suggested_post_ton`).
- Child fact tables (29 catalog children + TL-vector unions): scalar/peer facts `tf_messages_from_id`, `tf_messages_saved_peer_id`, `tf_messages_from_boosts_applied`, `tf_messages_from_rank`, `tf_messages_via_bot_id`, `tf_messages_via_business_bot_id`, `tf_messages_guestchat_via_from`, `tf_messages_views`, `tf_messages_forwards`, `tf_messages_edit_date`, `tf_messages_post_author`, `tf_messages_grouped_id`, `tf_messages_ttl_period`, `tf_messages_quick_reply_shortcut_id`, `tf_messages_effect`, `tf_messages_report_delivery_until_date`, `tf_messages_paid_message_stars`, `tf_messages_schedule_repeat_period`, `tf_messages_summary_from_language`; union children `tf_messages_reply_to`, `tf_messages_reply_markup`, `tf_messages_entities` (positioned, `constructor`), `tf_messages_restriction_reason` (positioned), `tf_messages_replies`, `tf_messages_reactions`, `tf_messages_factcheck`, `tf_messages_suggested_post`, `tf_messages_rich_message` (large RichText/BlockList union)…
- Shared catalog references (cross-reference only — see catalog family docs): `tf_message_medias` (via `media`), `tf_chats` (via `rich_message.document`+photo peer chains), `tf_reactions` (via `reactions.recent_reactions`/`top_reactors`).

### `tf_messages_service` (tl `Message` [service variant]; 29 tables)
- PK `(account_id, id)`; single ctor `messageService` → no discriminator.
- Columns: `account_id, id, peer_id_type, peer_id_id, date` + 7 service booleans (see splitter proof).
- Children: `from_id, saved_peer_id, reply_to` (union w/ `reply_from`, `quote_entities`), `reactions` (union), `ttl_period`, `action → tf_message_actions` (shared catalog node, discriminator present). `messageReplyHeader.reply_media` pulls the shared `tf_message_medias` node and its `poll_option` bytes column (hex).

### `tf_message_actions` (tl `MessageAction`; 2 tables)
- PK `(account_id, message_action_id)` (synthetic key — base[0] is `title`, no self-identifying natural key); discriminator `constructor` (67 ctors).
- Columns: `account_id, message_action_id, constructor, title`.
- Children: `tf_message_actions_users` (positioned 1:N, `value` BIGINT — MessageAction variable `users: Vector<long>`).
- Curation: only `title` + `users` mirrored; every other variant param (gift/star/payment/call data…) intentionally not stored. See deltas.

### `tf_message_entities` (tl `MessageEntity`; 1 table)
- PK `(account_id, message_entity_id)` (synthetic key, base[0]=`offset`); discriminator `constructor` (25 ctors incl. `inputMessageEntityMentionName`).
- Columns: `account_id, message_entity_id, constructor, offset, length`.
- Curation: `language/url/user_id/document_id/collapsed/date/old_text` variant fields not stored. See deltas.

### `tf_message_medias` (tl `MessageMedia`; 14 tables)
- PK `(account_id, message_media_id)` (empty base → synthetic key); discriminator `constructor` (19 ctors).
- Columns: `account_id, message_media_id, constructor, spoiler, live_photo`.
- Children: `tf_message_medias_ttl_seconds` (scalar fact), shared catalog refs `tf_photos` (via `photo`) and `tf_documents` (via `video`, incl. `tf_documents_attributes` with hex `waveform`).
- Curation: only the `messageMediaPhoto` shape (photo/ttl/video + spoiler/live_photo) mirrored; poll/geo/contact/invoice/story/giveaway media variants not stored. See deltas.

### `tf_forum_topics` (tl `ForumTopic`; 1 table)
- PK `(account_id, id)`; discriminator `constructor` (ctors: `forumTopicDeleted`, `forumTopic`).
- Columns: `account_id, id, constructor`.

### `tf_story_items` (tl `StoryItem`; 1 table)
- PK `(account_id, id)`; discriminator `constructor` (ctors: `storyItemDeleted`, `storyItemSkipped`, `storyItem`).
- Columns: `account_id, id, constructor`.

### `tf_todo_items` (tl `TodoItem`; 3 tables)
- PK `(account_id, id)`; single ctor `todoItem` (no discriminator).
- Columns: `account_id, id`.
- Children: `tf_todo_items_title` (FK→`TextWithEntities` union: `text` + positioned `entities`).

### `tf_todo_lists` (tl `TodoList`; 6 tables)
- PK `(account_id, todo_list_id)` (synthetic key — base[0] is the `title` FK, no natural key); single ctor (no discriminator).
- Columns: `account_id, todo_list_id, others_can_append, others_can_complete`.
- Children: `tf_todo_lists_title` (TextWithEntities union subtree) + positioned `tf_todo_lists_list` (Vector<TodoItem>: `value` + nested `title` union subtree).

## §14 / TL deltas (INFORMATIONAL — catalog + code win; nothing to fix)

1. **Reference FK to catalog parents is expressed by sharing the context-free node, not a RESTRICT FK.** When an FK→target is a catalog table, `resolveFkTarget()` returns the shared catalog node itself (`parentTf=''`, `fkOn=null`), so the writer emits no FK constraint for that edge (0 RESTRICT FKs in this family). Spec §7's `fkOn/fkRestrict` mechanism exists but is unused for catalog targets. Severity: informational — column layout is preserved; referential integrity across shared stores is handled by the account-scoped composite PK at runtime.
2. **Deliberate curl drops are catalog curation, not defects:** `tf_message_actions` (67/67 ctors → only `title`+`users`, 183 unmirrored params), `tf_message_medias` (75 unmirrored params — only the photo variant is stored), `tf_message_entities` (13 — `language/url/user_id/document_id/…`), `tf_forum_topics` (21 — full `forumTopic` body not stored), `tf_story_items` (26 — only `id`). All are INFORMATIONAL: the mirror is a deliberately minimal store keyed by id, enriched by the ingest layer.
3. **Legit drops audited and clean:** all `flags`/`flags2`/`flags3`-style `#` bitmask params and `_` fillers are skipped; `input*` ctors are never mirrored. `tf_messages` (51 params), `tf_messages_service` (16), `tf_todo_items` (2), `tf_todo_lists` (4): 0 unconsumed.
4. **Shared table concept:** `SHARED_CATALOG_CHILDREN=13` (relistings of `tf_message_medias`, `tf_photos`, `tf_documents`, `tf_reactions`, `tf_message_actions`, …). The migration writer dedupes by table name (`$emitted`), so these emit once.

## Fix log

| Commit | What | Why |
| --- | --- | --- |
| `48aaee09` | `src/Schema/Mirror/MirrorTableResolver.php` — split the base/children fold (base keeps the `isFactShape` gate; children now materialize EVERY non-key shape as a child table) | Catalog `children` scalar/peer facts (`views`, `from_rank`, `via_bot_id`, `saved_peer_id`, `from_boosts_applied`, `ttl_seconds`, …) were silently dropped — row existence is fact existence for children entries |
| `48aaee09` | `src/Schema/Mirror/MirrorFieldDecomposer.php` — pass `$hex` through on the `TEXT NOT NULL` branch | bytes fields resolved to hex TEXT without the `->hex` marker (invariant I2) |

Regression proof (in audit, all PASS): `tf_message_medias_ttl_seconds` + 19 scalar/peer children present; `waveform` column hex=true; service splitter bools + `tf_message_actions` discriminator present. Gates after fix: `vendor/bin/phpunit --bootstrap dev-bootstrap.php tests/Schema/Mirror` → 44 tests, 1839 assertions OK; `vendor/bin/phpstan analyse src/Schema/Mirror --level=5 --no-progress` → No errors.