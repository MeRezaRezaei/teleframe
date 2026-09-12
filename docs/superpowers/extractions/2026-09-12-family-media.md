# Family extraction: media/documents

- **Family:** media/documents
- **Roots (8):** `tf_documents`, `tf_photos`, `tf_sticker_sets`, `tf_themes`, `tf_wallpapers`, `tf_web_pages`, `tf_reactions`, `tf_saved_reaction_tags`
- **DISTINCT_TABLES:** 32  |  **WALK_NODES:** 56  |  **HEX_COLS:** 15 (6 distinct; shared subtrees counted per attachment)
- **Date:** 2026-09-12
- **Ground truth:** machine catalog `docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json`; TL wire `schema/sources/TL_telegram_v227.tl` (Layer 227); resolver `src/Schema/Mirror/MirrorTableResolver.php` @ `92009c76`.
- **Fix commit:** `92009c76` (bytes-hex flag loss + dropped scalar catalog children, systemic)

## Resolver context

Graph built by `MirrorTableResolver::resolveAll()` → per-root `resolveTable()`. Scalar catalog children become 1:1 fact tables (`{parent}_{field}`); `FK→` targets attach shared catalog subtrees; vector `1:N` children add `position`. Multi-ctor types produce union-decomposed children with a `constructor` discriminator column. The base loop (post-Fix-C) now routes scalar base shapes to parent columns and only escalates FK→/1:N shapes to child tables; children shapes (row existence = fact existence) are always materialized.

---

## NF5 invariant audit — PASS

| # | Check | Result |
|---|-------|--------|
| NF5-1 | `account_id` BIGINT NOT NULL is first column in every table | PASS |
| NF5-2 | Known column types only (`BigInt, Integer, Boolean, String, TinyInt, Double`); no nullable/json/blob/binary; bytes fields marked hex | PASS |
| NF5-3 | PK = `(account_id, keyColumns[, position])`, all NOT NULL | PASS |
| NF5-4 | `constructor` discriminator present iff type has >1 non-input ctor | PASS |
| NF5-5 | Peer pairs (`{field}_type` + `{field}_id`) always co-occur | PASS |
| NF5-6 | FK semantics: shared catalog targets cascade via `parentTf`; standalone targets via `fkOn` | PASS |
| NF5-7 | No duplicate child table names under one parent | PASS |

**Gates:** `phpunit tests/Schema/Mirror` → 44 tests, 1839 assertions, OK. `phpstan analyse src/Schema/Mirror --level=5` → 0 errors. Regression assertion script → `REGRESSION=PASS`, `ALL BYTES PARAMS ARE HEX`.

---

## Per-root table documentation

### tf_documents — 7 walk nodes

**Root columns:** `account_id` BigInt, `id` BigInt, `constructor` String (union: `document`/`documentEmpty`), `access_hash` BigInt, `file_reference` hex String:255, `date` Integer, `mime_type` String, `size` BigInt, `dc_id` Integer

| Child table | Parent | Role | Positioned | Key columns |
|---|---|---|---|---|
| `tf_documents_attributes` | tf_documents | Union ctor-child (document.document Attributes variant) | YES | (account_id, id, position) |
| `tf_documents_attributes_mask_coords` | tf_documents_attributes | Union ctor-child (maskCoords) | NO | (account_id, id) |
| `tf_documents_thumbs` | tf_documents | Union ctor-child (photoSize variants) | YES | (account_id, id, position) |
| `tf_documents_thumbs_sizes` | tf_documents_thumbs | Vector child (sizes vector) | YES | (account_id, id, position) |
| `tf_documents_video_thumbs` | tf_documents | Union ctor-child (videoSize/photoSize variants) | YES | (account_id, id, position) |
| `tf_documents_video_thumbs_background_colors` | tf_documents_video_thumbs | Vector child | YES | (account_id, id, position) |

**`tf_documents_attributes` columns:** `account_id`, `id`, `position`, `constructor` (20+ ctor union: `documentAttributeAnimated`, `documentAttributeVideo`, `documentAttributeAudio`, `documentAttributeSticker`, `documentAttributeImageSize`, `documentAttributeVideoSize` etc.), `w` Integer, `h` Integer, `mask` Boolean, `alt` String, `round_message` Boolean, `supports_streaming` Boolean, `nosound` Boolean, `duration` Double, `preload_prefix_size` Integer, `video_start_ts` Double, `video_codec` String, `voice` Boolean, `title` String, `performer` String, `waveform` hex String, `file_name` String, `free` Boolean, `text_color` Boolean

**`tf_documents_thumbs` columns:** `account_id`, `id`, `position`, `constructor`, `type` String, `w` Integer, `h` Integer, `size` Integer, `bytes` hex String

**`tf_documents_video_thumbs` columns:** `account_id`, `id`, `position`, `constructor`, `type` String, `w` Integer, `h` Integer, `size` Integer, `video_start_ts` Double, `emoji_id` BigInt, `sticker_id` BigInt

**Hex fields (6 occurrences per shared-traversal):** `file_reference` (root), `waveform` (attributes), `bytes` (thumbs)

---

### tf_photos — 5 walk nodes

**Root columns:** `account_id` BigInt, `id` BigInt, `constructor` String (union: `photo`/`photoEmpty`), `access_hash` BigInt, `file_reference` hex String:255, `date` Integer, `dc_id` Integer, `has_stickers` Boolean (bools)

| Child table | Parent | Role | Positioned | Key columns |
|---|---|---|---|---|
| `tf_photos_sizes` | tf_photos | Union ctor-child (photoSize variants) | YES | (account_id, id, position) |
| `tf_photos_sizes_sizes` | tf_photos_sizes | Vector child (sizes vector) | YES | (account_id, id, position) |
| `tf_photos_video_sizes` | tf_photos | Union ctor-child (videoSize variants) | YES | (account_id, id, position) |
| `tf_photos_video_sizes_background_colors` | tf_photos_video_sizes | Vector child | YES | (account_id, id, position) |

**`tf_photos_sizes` columns:** `account_id`, `id`, `position`, `constructor`, `type` String, `w` Integer, `h` Integer, `size` Integer, `bytes` hex String

**`tf_photos_video_sizes` columns:** `account_id`, `id`, `position`, `constructor`, `type` String, `w` Integer, `h` Integer, `size` Integer, `video_start_ts` Double, `emoji_id` BigInt, `sticker_id` BigInt

**Hex fields:** `file_reference` (root), `bytes` (sizes)

---

### tf_sticker_sets — 7 walk nodes

**Root columns:** `account_id` BigInt, `id` BigInt, `access_hash` BigInt, `title` String, `short_name` String, `count` Integer, `hash` Integer, `archived` Boolean, `official` Boolean, `masks` Boolean, `emojis` Boolean, `text_color` Boolean, `channel_emoji_status` Boolean, `creator` Boolean (7 bools)

Single ctor `stickerSet` — no constructor discriminator column.

| Child table | Parent | Role | Positioned | Key columns |
|---|---|---|---|---|
| `tf_sticker_sets_installed_date` | tf_sticker_sets | 1:1 fact (scalar `installed_date`) | NO | (account_id, id) |
| `tf_sticker_sets_thumbs` | tf_sticker_sets | Union ctor-child (photoSize variants) | YES | (account_id, id, position) |
| `tf_sticker_sets_thumbs_sizes` | tf_sticker_sets_thumbs | Vector child | YES | (account_id, id, position) |
| `tf_sticker_sets_thumb_dc_id` | tf_sticker_sets | 1:1 fact (scalar) | NO | (account_id, id) |
| `tf_sticker_sets_thumb_version` | tf_sticker_sets | 1:1 fact (scalar) | NO | (account_id, id) |
| `tf_sticker_sets_thumb_document_id` | tf_sticker_sets | 1:1 fact (scalar) | NO | (account_id, id) |

**`tf_sticker_sets_thumbs` columns:** `account_id`, `id`, `position`, `constructor`, `type` String, `w` Integer, `h` Integer, `size` Integer, `bytes` hex String

**Hex fields:** `bytes` (thumbs)

---

### tf_themes — 22 walk nodes (includes shared subtrees)

**Root columns:** `account_id` BigInt, `id` BigInt, `access_hash` BigInt, `slug` String, `title` String, `creator` Boolean, `default` Boolean, `for_chat` Boolean (3 bools)

Single ctor `theme` — no constructor discriminator column.

| Child table | Parent | Role | Positioned | Key columns |
|---|---|---|---|---|
| `tf_documents` | tf_themes | **Shared catalog FK→Document** (full subtree, see tf_documents above) | — | — |
| `tf_themes_settings` | tf_themes | Union ctor-child (themeSettings variants) | YES | (account_id, id, position) |
| `tf_themes_settings_base_theme` | tf_themes_settings | Union ctor-child (baseTheme variants) | NO | (account_id, id) |
| `tf_themes_settings_message_colors` | tf_themes_settings | Vector child | YES | (account_id, id, position) |
| `tf_wallpapers` | tf_themes_settings | **Shared catalog FK→WallPaper** (full subtree, see tf_wallpapers below) | — | — |
| `tf_themes_emoticon` | tf_themes | 1:1 fact (scalar) | NO | (account_id, id) |
| `tf_themes_installs_count` | tf_themes | 1:1 fact (scalar) | NO | (account_id, id) |

**`tf_themes_settings` columns:** `account_id`, `id`, `position`, `message_colors_animated` Boolean, `accent_color` Integer, `outbox_accent_color` Integer

**`tf_themes_settings_base_theme` columns:** `account_id`, `id`, `constructor` (baseThemeClassic, baseThemeDay, baseThemeNight, etc.)

---

### tf_wallpapers — 9 walk nodes (includes shared tf_documents subtree)

**Root columns:** `account_id` BigInt, `id` BigInt, `constructor` String (union: `wallPaper`/`wallPaperNoFile`), `access_hash` BigInt, `slug` String, `creator` Boolean, `default` Boolean, `pattern` Boolean, `dark` Boolean (4 bools)

| Child table | Parent | Role | Positioned | Key columns |
|---|---|---|---|---|
| `tf_documents` | tf_wallpapers | **Shared catalog FK→Document** (full subtree, see tf_documents above) | — | — |
| `tf_wallpapers_settings` | tf_wallpapers | 1:1 fact (FK→WallPaperSettings inline union decomposition) | NO | (account_id, id) |

**`tf_wallpapers_settings` columns:** `account_id`, `id`, `blur` Boolean, `motion` Boolean, `background_color` Integer, `second_background_color` Integer, `third_background_color` Integer, `fourth_background_color` Integer, `intensity` Integer, `rotation` Integer, `emoticon` String

---

### tf_web_pages — 2 walk nodes

**Root columns:** `account_id` BigInt, `id` BigInt, `constructor` String (union: `webPage`/`webPageEmpty`/`webPagePending`/`webPageNotModified`), `date` Integer

| Child table | Parent | Role | Positioned | Key columns |
|---|---|---|---|---|
| `tf_web_pages_url` | tf_web_pages | 1:1 fact (scalar) | NO | (account_id, id) |

**`tf_web_pages_url` columns:** `account_id`, `id`, `url` String

---

### tf_reactions — 1 walk node

**Root columns:** `account_id` BigInt, `reaction_id` BigInt (synthetic key — base[0]=`emoticon` is not id/peer), `constructor` String (union: `reactionEmoji`/`reactionCustomEmoji`/`reactionPaid`/`reactionEmpty`), `emoticon` String

No child tables. Constructor discriminator only; `reactionCustomEmoji.document_id` and `reactionPaid.reaction_id` are catalog-curated omissions (informational — see §14 deltas below).

---

### tf_saved_reaction_tags — 3 walk nodes (includes shared tf_reactions)

**Root columns:** `account_id` BigInt, `saved_reaction_tag_id` BigInt (synthetic key — base[0]=`reaction` is FK→Reaction, not id/peer), `count` Integer

Single ctor `savedReactionTag` — no constructor discriminator column.

| Child table | Parent | Role | Positioned | Key columns |
|---|---|---|---|---|
| `tf_reactions` | tf_saved_reaction_tags | **Shared catalog FK→Reaction** (ctor-discriminated union, 1 node) | — | — |
| `tf_saved_reaction_tags_title` | tf_saved_reaction_tags | 1:1 fact (scalar) | NO | (account_id, saved_reaction_tag_id) |

**`tf_saved_reaction_tags_title` columns:** `account_id`, `saved_reaction_tag_id`, `title` String

---

## Shared catalog children (cross-references)

The resolver caches shared catalog targets by tfName and attaches the full subtree wherever an FK reference lands. The following shared tables appear at multiple attachment points:

| Shared target | Attachment points | Walk nodes per attachment |
|---|---|---|
| `tf_documents` (full 7-node subtree) | root; under `tf_themes` (FK→Document); under `tf_wallpapers` root (FK→Document); under `tf_themes_settings > tf_wallpapers` (FK→Document) | 7 each |
| `tf_wallpapers` (full subtree incl. tf_documents) | root; under `tf_themes_settings` (FK→WallPaper) | 9 each |
| `tf_reactions` | root; under `tf_saved_reaction_tags` (FK→Reaction) | 1 each |

These shared targets are deduplicated by tfName within each attachment point. Walk node count (56) reflects multiple traversals; distinct table count (32) reflects unique tfNames.

---

## §14 / TL informational deltas

All 22 non-consumed params reported by conformance audit are **informational** — catalog curation or matcher false negatives, not resolver defects.

### Catalog-curated omissions (not materialized by design)

| Root | Param | Reason |
|---|---|---|
| `tf_reactions` | `reactionCustomEmoji.document_id` | Catalog only curates `emoticon` in base; `document_id` (from `reactionCustomEmoji` ctor) intentionally omitted. Union decomposition uses curated base only. |
| `tf_web_pages` | 19 params (`site_name`, `title`, `description`, `photo`, `embed_url`, `duration`, `author`, etc.) | Catalog curates only `date` + `url` child. The full `webPage` TL type has ~20 params but only a minimal mirror is desired. |
| `tf_web_pages` | `webPageNotModified.cached_page_views` | Catalog omission per above. |

### Matcher false negatives (tables/cols exist, matcher name mismatch)

| Root | Param | Reason |
|---|---|---|
| `tf_themes` | `theme.document` | Shared `tf_documents` attached as child — conformance matcher expects `{root}_document` but shared target name is `tf_documents`. |
| `tf_wallpapers` | `wallPaper.document` | Same shared-target matcher false negative. |

---

## Defect fix log

### Fix: bytes-hex flag loss and dropped scalar catalog children (commit `92009c76`)

**Two systemic defects fixed; changes affect all families, not just media.**

1. **MirrorFieldDecomposer::scalarColumn** — `$hex` param was only honored in the `BIGINT` branch; `VARCHAR` and `TEXT NOT NULL` String branches silently discarded the hex flag. Fixed by passing `$hex` through both String branches.

2. **MirrorTableResolver::buildBaseColumns + resolveTable** — base scalar shapes (e.g. `installed_date INTEGER NOT NULL`) were gated behind `isFactShape()` which only matched FK→/1:N, dropping scalar children entirely. Split the loop: base entries use `isFactShape()` to route FK→/1:N to child tables (scalars stay as parent columns); children entries always materialize via `resolveChildField()`. Added `isBytesBaseField()` helper for the `buildBaseColumns` call.

**Files modified:**
- `src/Schema/Mirror/MirrorFieldDecomposer.php` — scalarColumn hex propagation (lines 72–83)
- `src/Schema/Mirror/MirrorTableResolver.php` — buildBaseColumns (lines 609–620), new isBytesBaseField (lines 632–640), resolveTable children loop split (post-620 block)

**Impact:** Before fix: TOTAL_TABLES=48, HEX_COLS=0 (bytes fields invisible to hex marker), 85 scalar child tables across full catalog dropped. After fix: TOTAL_TABLES=56 (media family only), HEX_COLS=15, 85 scalar children materialized across all families.
