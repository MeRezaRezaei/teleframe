# Family extraction: identity/core mirror tables

- **Family:** identity/core
- **Roots (8):** `tf_users`, `tf_chats`, `tf_dialogs`, `tf_saved_dialogs`, `tf_folders`, `tf_dialog_filters`, `tf_channel_participants`, `tf_encrypted_chats`
- **TOTAL_TABLES:** 89 (roots + synthesized facts)
- **Date:** 2026-09-12
- **Worktree:** `agent-a3a6dce7` (branch `worktree-agent-a3a6dce7`, baseline `78ece705`)
- **Contracts:** `schema/sources/TL_telegram_v227.tl` (wire ground truth), `docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json` (machine contract — wins over prose), `docs/superpowers/specs/2026-09-11-telegram-mirror-schema-nf5-design.md` §14 (descriptive; deltas are INFORMATIONAL)

General posture: **code wins over prose.** The catalog is the contract; the resolver must materialize exactly what the catalog declares. §14 prose that diverges from the catalog is recorded below as INFORMATIONAL, never "fixed".

Conventions: every mirror table starts with `account_id BIGINT` (tenant scope, first part of every PK) and multi-constructor roots carry a `constructor TEXT` discriminator; these two columns are omitted from the per-root listings. `*` marks a `positioned` (1:N vector) table with a `position` PK component.

## NF5 invariant gate — PASS

Audit: `/tmp/audit_invariants.php` (runs NF5 rules 1–7 + catalog conformance + 22 regression assertions against the flattened 89-table graph).

| Check | Result |
| --- | --- |
| NF5-1 account_id first column, BigInt | PASS |
| NF5-2 only known enum types (no json/blob/nullable/binary) | PASS |
| NF5-3 PK composite = (account_id, keyColumns[, position]) | PASS |
| NF5-4 multi-ctor tables carry `constructor` discriminator | PASS |
| NF5-5 peer columns always type/id pairs | PASS |
| NF5-6 child FK semantics (parentTf or shared catalog) | PASS |
| NF5-7 no duplicate sibling child names | PASS |
| Catalog conformance — every base/children/bools entry materializes | PASS |
| Regression — 22 previously-dropped items now materialize | PASS |

- **RESULT=PASS**, `TOTAL_TABLES=89`.
- Per-root subtree counts: tf_users=24, tf_chats=5, tf_dialogs=45, tf_saved_dialogs=1, tf_folders=2, tf_dialog_filters=8, tf_channel_participants=3, tf_encrypted_chats=1 (sum 89).
- Gate: `vendor/bin/phpunit tests/Schema/Mirror --no-progress` → **OK (44 tests, 1839 assertions)**.
- Gate: `vendor/bin/phpstan analyse src/Schema/Mirror --level=5 --no-progress` → **0 errors**.
- Cross-root note: `tf_chats` (a catalog entry) is aliased in two tree positions — at ROOT and nested under `tf_dialogs_draft_rich_message_blocks` (PageBlock `chatLink` embed). Both aliases are the same shared-catalog object with the same 4 children; sibling-dup rule NF5-7 uses per-parent lists, so this is not a violation. It is the only catalog-entry table nested in this family (verified: `/tmp/shared_cat_nodes.php` reports no other catalog-owner tables under any of the 8 roots).

## Per-root docs

### tf_users (24 tables)

- Source: tlType=`User`, 2 ctors (`userEmpty`/`user`), PK=`[id]`, constructor discriminator: yes.
- Base columns (non-account_id/non-constructor): `id BIGINT PK`; 31 bools below.
- Presence bools: `self, contact, mutual_contact, deleted, bot, bot_chat_history, bot_nochats, verified, restricted, min, bot_inline_geo, support, scam, apply_min_photo, fake, bot_attach_menu, premium, attach_menu_enabled, bot_can_edit, close_friend, stories_hidden, stories_unavailable, contact_require_premium, bot_business, bot_has_main_app, bot_forum_view, bot_forum_can_manage_topics, bot_can_manage_bots, bot_guestchat, bot_guard` (31).
- Children (direct, 19): `access_hash, first_name, last_name, username, phone, photo, status, bot_info_version` (1:1 parentTf); `restriction_reason*` (POS), `bot_inline_placeholder, lang_code, emoji_status` (1:1), `usernames*` (POS), `stories_max_id, color, profile_color, bot_active_users, bot_verification_icon, send_paid_messages_stars` (1:1). `color` and `profile_color` each carry nested POS vectors `colors*`, `dark_colors*`.
- TL audit: consumed=51, legit drops=2 (empty-ctor masks). All non-mask params consumed.
- §14 deltas: none — spec child list (lines 941–977) matches code exactly, including `bot_verification_icon` and `send_paid_messages_stars`.

### tf_chats (5 tables)

- Source: tlType=`Chat`, 5 ctors (`channel`, `channelForbidden`, `chat`, `chatForbidden`, `empty` — `empty` is inherently param-free), PK=`[id]`, constructor discriminator: yes.
- Base columns: `id BIGINT PK`; `title TEXT`; `participants_count INTEGER`; `date INTEGER`; `version INTEGER`; bools: `creator, left, deactivated, call_active, call_not_empty, noforwards`.
- Children (direct, 4): `photo, migrated_to, admin_rights, default_banned_rights` (all 1:1 parentTf).
- TL audit: consumed=35, legit drops=4. Unconsumed are variant payloads the catalog intentionally does not map (INFORMATIONAL — see §14 below): 23 `channel.*` true-flags, `channel.username`, `channel.restriction_reason`, `channel.banned_rights`, `channel.usernames`, `channel.stories_max_id`, `channel.color`, `channel.profile_color`, `channel.emoji_status`, `channel.level`, `channel.subscription_until_date`, `channel.bot_verification_icon`, `channel.send_paid_messages_stars`, `channel.linked_monoforum_id`, plus 3 `channelForbidden.*` true-flags.
- §14 deltas (INFORMATIONAL): §14 lists `photo` as a **base** column `FK→ChatPhoto` (line 394); the resolver materializes it as the child fact table `tf_chats_photo`. The catalog (`base` entry `photo`) is the contract; FK-shape entries route to children by design.

### tf_dialogs (45 tables)

- Source: tlType=`Dialog`, 2 ctors (`dialog`/`dialogFolder`), PK=`[peer_type, peer_id]`, constructor discriminator: yes.
- Base columns: `peer_type TINYINT PK peer`, `peer_id BIGINT PK peer` (peer pair); `top_message, read_inbox_max_id, read_outbox_max_id, unread_count, unread_mentions_count, unread_reactions_count, unread_poll_votes_count INTEGER`; bools: `pinned, unread_mark, view_forum_as_messages`.
- Children (direct, 5): `notify_settings` (1:1 → 6 nested sound scalars), `pts` (1:1), `draft` (1:1 → the largest subtree: `entities*`, `suggested_post→price`, `rich_message→blocks*` [19 positioned blocks incl. `tf_chats` chatLink alias, `rows*→cells*`], `photos*→sizes*`, `documents*`), `folder_id` (1:1), `ttl_period` (1:1).
- TL audit: consumed=20, legit drops=2. Unconsumed: 4 `dialogFolder.unread_*_counts` scalars (variant payload the catalog omits).
- §14 deltas (INFORMATIONAL): §14 lists `notify_settings` as a **base** column `FK→PeerNotifySettings` (line 427); code materializes the child fact `tf_dialogs_notify_settings`. Catalog is the contract.

### tf_saved_dialogs (1 table)

- Source: tlType=`SavedDialog`, 2 ctors (`savedDialog`/`monoForumDialog`), PK=`[peer_type, peer_id]`, constructor discriminator: yes.
- Base columns: `peer_type TINYINT PK peer`, `peer_id BIGINT PK peer`; `top_message INTEGER`; bool: `pinned`.
- Children: none (leaf root).
- TL audit: consumed=5, legit drops=2. Unconsumed (monoForumDialog variant): `unread_mark`, `nopaid_messages_exception`, `read_inbox_max_id`, `read_outbox_max_id`, `unread_count`, `unread_reactions_count`, `draft`.
- §14 deltas (INFORMATIONAL): spec (lines 716–729) documents the plain `savedDialog` shape; the `monoForumDialog` ctor params are not reflected in the catalog. Catalog omits them by design.

### tf_folders (2 tables)

- Source: tlType=`Folder`, 1 ctor (`folder`), PK=`[id]`, constructor discriminator: none.
- Base columns: `id INTEGER PK`; `title TEXT`; bools: `autofill_new_broadcasts, autofill_public_groups, autofill_new_correspondents`.
- Children (direct, 1): `photo` (1:1).
- TL audit: consumed=6, legit drops=1. All non-mask params consumed.
- §14 deltas: none.

### tf_dialog_filters (8 tables)

- Source: tlType=`DialogFilter`, 3 ctors (`dialogFilter`, `dialogFilterChatlist`, `dialogFilterDefault`), PK=`[id]`, constructor discriminator: yes.
- Base columns: `id INTEGER PK`; 9 bools: `contacts, non_contacts, groups, broadcasts, bots, exclude_muted, exclude_read, exclude_archived, title_noanimate`.
- Children (direct, 6): `title` (1:1 → nested `title_entities*` POS), `pinned_peers*`, `include_peers*`, `exclude_peers*` (POS, curated InputPeer vector targets), `emoticon`, `color` (1:1).
- TL audit: consumed=23, legit drops=2. Unconsumed: `dialogFilterChatlist.has_my_invites` (true-flag variant).
- §14 deltas (INFORMATIONAL): spec (lines 442–465) matches the catalog shape; the `has_my_invites` chatlist flag is intentionally unmapped.

### tf_channel_participants (3 tables)

- Source: tlType=`ChannelParticipant`, 6 ctors (`channelParticipant`, `channelParticipantSelf`, `channelParticipantCreator`, `channelParticipantAdmin`, `channelParticipantBanned`, `channelParticipantLeft`), PK=`[user_id]`, constructor discriminator: yes.
- **Key derivation note:** the natural-key rule (`str_ends_with(base[0], '_id')`) makes `user_id` the PK, superseding the earlier synthetic `channel_participant_id` derivation.
- Base columns: `user_id BIGINT PK`; `date INTEGER`. No bools.
- Children (direct, 2): `subscription_until_date`, `rank` (1:1).
- TL audit: consumed=15, legit drops=5. Unconsumed (variant payloads the catalog does not map): `via_request`, `inviter_id`, `admin_rights`, `can_edit`, `self`, `promoted_by`, `banned_rights`, `left`, `peer`, `kicked_by`.
- §14 deltas (INFORMATIONAL): spec (lines 366–382) describes the common core only (`user_id`, `date`); ctor-variant payloads above are intentionally unmapped.

### tf_encrypted_chats (1 table)

- Source: tlType=`EncryptedChat`, 5 ctors (`encryptedChat`, `encryptedChatRequested`, `encryptedChatWaiting`, `encryptedChatDiscarded`, `empty`), PK=`[id]`, constructor discriminator: yes.
- Base columns: `id INTEGER PK`; `access_hash BIGINT`; `date INTEGER`; `admin_id BIGINT`; `participant_id BIGINT`. No bools.
- Children: none (leaf root).
- TL audit: consumed=17, legit drops=2. Unconsumed (crypto session payloads, intentionally unmapped): `folder_id`, `g_a`, `g_a_or_b`, `key_fingerprint`, `history_deleted`.
- §14 deltas (INFORMATIONAL): spec (lines 491–503) matches the catalog's base shape; DH/kDF session payloads live only on wire and are not relational facts.

## Fix log

Commit `1f025825` — `fix(schema): materialize scalar children and curated Input* targets` (33 insertions, 10 deletions, `src/Schema/Mirror/MirrorTableResolver.php` only).

- **Fix A:** split the `resolveTable` base/children loops — the base loop routes only fact shapes (`FK→`/`1:N child`) via `resolveChildField` (scalar fallback synthesizes a `{parent}_{field}` 1:1 child), and a separate children loop routes **all** catalog children through `appendChild`. This re-materializes the 22 scalar 1:1 fact tables that the combined loop had silently swallowed (e.g. `tf_users_first_name`, `tf_dialogs_pts`, `tf_dialog_filters_title`, `tf_channel_participants_rank`).
- **Fix B:** `unionDecompose(..., bool $allowInputCtor = false)`; the ctor filter drops `input*` constructors unless `allowInputCtor` is true. `resolveFkTarget`/`resolveVectorFromType` pass `$this->catalog->has($parentTf)`, so curated catalog children targeting `InputChannel`/`InputPeer` materialize (`tf_chats_migrated_to`, `tf_dialog_filters_pinned_peers`) while dynamic nested union trees stay `input`-free (no tree explosion).
- Regression assertions covering all 22 items were added to the audit script and pass (see invariant gate).

## Extras

- Audit scripts: `/tmp/audit_invariants.php`, `/tmp/audit_per_root.php`, `/tmp/audit_full_dump.php`, `/tmp/audit_tree.php`, `/tmp/tl_conformance.php`, `/tmp/shared_cat_nodes.php`, `/tmp/bootstrap_mywt.php` (worktree PSR-4 pin; shared vendor autoload resolves against this worktree's `src/`).
- No changes to `generated/`, `migrations/`, other families' docs, or `src/` outside `Schema/Mirror`.