# Family extraction: bots / business / admin — 2026-09-12

Mirror family `bots` — 7 root catalog tables, extracted and audited against
`TL_telegram_v227.tl` and the NF5 design spec §14 (prose; catalog + code win).

| | |
|---|---|
| Roots | `tf_bot_apps`, `tf_bot_infos`, `tf_bot_inline_results`, `tf_attach_menu_bots`, `tf_business_chat_links`, `tf_admin_log_events`, `tf_quick_replies` |
| Resolved tables | **169** (roots + synthesized children + shared catalog subtrees) |
| Shared catalogs attached | `tf_photos`, `tf_documents`, `tf_chats`, `tf_messages_service`, `tf_message_medias` (owned by other families; cross-referenced below) |
| Resolver | `MirrorTableResolver` @ `78ece705` + fix commit `e3b4e4a9` |

## NF5 invariant audit — PASS (all 7)

| # | Invariant | Result |
|---|---|---|
| 1 | `account_id` first column on every table | PASS (169/169) |
| 2 | Zero nullable / json / blob / binary columns; bytes → hex TEXT | PASS (`file_reference`, `waveform`, `bytes`, `data` flagged hex) |
| 3 | PK = (`account_id`, keyCols[, `position`]) | PASS |
| 4 | Multi-ctor tables carry `constructor` discriminator | PASS (`tf_bot_apps`, `tf_bot_inline_results`, `tf_admin_log_events`, union children) |
| 5 | Peer shapes = `{field}_type` + `{field}_id` | PASS (in `tf_chats`/`tf_messages_service` subtrees) |
| 6 | child→parent CASCADE (`parentTf`); object-ref FK RESTRICT (`fkOn`) | PASS (FK→ targets: `tf_photos`, `tf_documents`, `tf_web_pages`, union children) |
| 7 | No duplicate child tables under one parent | PASS (hasChild dedup on scalar children) |

Evidence: `php audit_bots.php` — `TOTAL_TABLES=169 … MEMORY=14MB`; `phpunit tests/Schema/Mirror` 46 green; `phpstan src/Schema/Mirror --level=5` 0 errors.

## Per-root extraction

### `tf_attach_menu_bots` — TL `AttachMenuBot` (ctor `attachMenuBot`)

- **Key:** `(account_id, bot_id)` — **FIXED**: natural `bot_id` replaced fabricated `attach_menu_bot_id` (was a nonexistent TL field).
- **Base columns:** `bot_id` BIGINT, `short_name` TEXT.
- **Flags:** `inactive`, `has_settings`, `request_write_access`, `show_in_attach_menu`, `show_in_side_menu`, `side_menu_disclaimer_needed`.
- **Children:**
  | Table | Role |
  |---|---|
  | `tf_attach_menu_bots_icons` | 1:N vector (POS) → embeds `tf_documents`, `tf_attach_menu_bots_icons_colors` |
  | `tf_attach_menu_bots_peer_types` | 1:N vector (POS) |
- **TL audit:** every `attachMenuBot` param consumed; `flags` masks dropped only.

### `tf_bot_apps` — TL `BotApp` (ctors `botAppNotModified`, `botApp`)

- **Key:** `(account_id, id)`.
- **Base columns:** `id` BIGINT, `access_hash`, `short_name`, `title`, `description`, `hash`.
- **Constructor discriminator:** `constructor`.
- **Children:** `tf_photos` (FK→Photo, shared catalog), `tf_documents` (FK→Document, shared).
- **TL audit:** `botAppNotModified` param `flags` dropped; `botApp` fully consumed.

### `tf_bot_infos` — TL `BotInfo` (ctor `botInfo`)

- **Key:** `(account_id, bot_info_id)` — synthetic (empty base; no self-identifying TL field).
- **Flags:** `has_preview_medias`.
- **Children** (optional scalars materialize as 1:1 fact tables — **FIXED**, previously dropped):
  | Table | Fact |
  |---|---|
  | `tf_bot_infos_user_id` | `user_id` BIGINT |
  | `tf_bot_infos_description` | `description` TEXT |
  | `tf_photos` (shared) | `description_photo` FK→Photo |
  | `tf_documents` (shared) | `description_document` FK→Document |
  | `tf_bot_infos_commands` | 1:N vector (POS), `command`/`description` |
  | `tf_bot_infos_menu_button` | FK→BotMenuButton (union: `text`/`url`) |
  | `tf_bot_infos_privacy_policy_url` | `privacy_policy_url` TEXT |
  | `tf_bot_infos_app_settings` | `placeholder_path` + colors |
  | `tf_bot_infos_verifier_settings` | `can_modify_custom_description`, `icon`, `company`, `custom_description` |
- **TL audit:** full `botInfo` ctor consumed — matches §14 exactly.

### `tf_bot_inline_results` — TL `BotInlineResult` (ctors `botInlineResult`, `botInlineMediaResult`)

- **Key:** `(account_id, id)`; base `id` TEXT, `type` TEXT; `constructor` discriminator.
- **Children:**
  | Table | Role |
  |---|---|
  | `tf_bot_inline_results_send_message` | FK→BotInlineMessage union: `entities` (POS), `reply_markup` (`rows`→`buttons`→`style`/`peer_types`/`peer_type`), `geo`, `photo`, `rich_message` |
  | `tf_bot_inline_results_title` | scalar 1:1 |
  | `tf_bot_inline_results_description` | scalar 1:1 |
  | `tf_bot_inline_results_url` | scalar 1:1 |
  | `tf_bot_inline_results_thumb` | FK→WebDocument (synthesized subtree `_attributes`) |
  | `tf_bot_inline_results_content` | FK→WebDocument (subtree `_attributes` + `_mask_coords`) |
- **TL audit:** all optional params consumed as children; matches §14.

### `tf_business_chat_links` — TL `BusinessChatLink` (ctor `businessChatLink`)

- **Key:** `(account_id, business_chat_link_id)` — synthetic (base[0]=`link` TEXT is not id-like; correct).
- **Base columns:** `link` TEXT, `message` TEXT, `views` INTEGER.
- **Children:** `tf_business_chat_links_entities` (1:N vector, POS), `tf_business_chat_links_title` (scalar 1:1).
- **TL audit:** full ctor consumed.

### `tf_admin_log_events` — TL `ChannelAdminLogEvent` + `ChannelAdminLogEventAction` (52 ctors)

- **Key:** `(account_id, id)`; `constructor` discriminator on the action union.
- **Children:** `tf_admin_log_events_action` — synthesized action union embedding shared catalogs (`tf_photos`, `tf_messages_service` + its `from_id`/`saved_peer_id`/`reply_to`/`tf_message_medias` subtree).
- **TL audit:** action ctor set fully materialized as one union child with discriminator.

### `tf_quick_replies` — TL `QuickReply` (ctor `quickReply`)

- **Key:** `(account_id, shortcut_id)` — **FIXED**: natural `shortcut_id` replaced fabricated `quick_replie_id`.
- **Base columns:** `shortcut_id` INTEGER, `shortcut` TEXT, `top_message` INTEGER, `count` INTEGER.
- **Children:** none.
- **TL audit:** full ctor consumed; matches §14.

## §14 deltas (informational — catalog + code win)

| §14 prose | Resolver | Severity |
|---|---|---|
| `tf_bot_infos` children incl. `description_photo` → `tf_bot_infos_description_photo` | shared `tf_photos` (catalog target) | INFO — shared-catalog naming per T4a |
| `tf_bot_inline_results.thumb/content` FK→WebDocument as 1:1 | synthesized `tf_bot_inline_results_thumb/content` + WebDocument subtree (attributes) | INFO — union decomposition |
| §14 lists keys implicitly | natural `*_id` keys for `bot_id`/`shortcut_id`/`user_id` | FIXED above |

## Fix log

- `e3b4e4a9` — scalar catalog children materialize as fact tables (was: silently dropped); natural `*_id` base keys replace fabricated `{singular}_id` PKs. Regression tests: `test_scalar_catalog_children_materialize_as_fact_tables`, `test_natural_id_base_fields_become_keys_not_synthetic`. `phpunit` 46 green, phpstan 0 errors.