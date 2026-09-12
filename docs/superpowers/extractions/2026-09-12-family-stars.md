# Stars / Calls / Misc Mirror Family Extraction

**Family:** stars/calls/misc
**Roots:** `tf_stars_transactions`, `tf_stars_subscriptions`, `tf_saved_star_gifts`, `tf_phone_calls`, `tf_group_calls`
**TOTAL_TABLES:** 220
**Date:** 2026-09-12

---

## NF5 Invariant Pass/Fail

All 7 invariants pass on the full 220-table tree. Evidence from `inv_stars2.php`:

```
=== TABLE COUNTS PER ROOT ===
tf_stars_transactions        166 tables
tf_stars_subscriptions       8 tables
tf_saved_star_gifts          41 tables
tf_phone_calls               4 tables
tf_group_calls               1 tables
TOTAL_TABLES=220

=== INVARIANT VIOLATIONS ===
ALL PASS
REGRESSION OK: tf_phone_calls_receive_date present
REGRESSION OK: nested extended_media positioned
REGRESSION_ALL_PASS
```

| Rule | Assertion | Status |
|------|-----------|--------|
| R1 account_id first | Every table begins with `account_id BIGINT NOT NULL` as column 0 | PASS |
| R2 PK composition | PKs = `(account_id, keyColumns[, position])` for positioned children | PASS |
| R3 Constructor discriminator | Multi-ctor roots (`tf_phone_calls`=6, `tf_group_calls`=2) carry `constructor TEXT NOT NULL`; single-ctor roots omit it | PASS |
| R4 Peer shapes | Peer fields resolve to `{field}_type TINYINT` + `{field}_id BIGINT` (e.g. `tf_stars_transactions.peer`, `tf_stars_subscriptions.peer`) | PASS |
| R5 No duplicate siblings | No sibling tables share a name under the same parent | PASS |
| R6 FK composition | Object-reference FKs carry `fkOn`, `fkCols`, `fkToCols`, `fkRestrict`; parentTf on child tables is set | PASS |
| R7 No nullable/json | Zero `NULL` types, zero `JSON`/`BLOB` columns; all fields are `NOT NULL` with typed enums | PASS |

Regression assertions:
- `tf_phone_calls_receive_date` present with `keyColumns=['id']` -- PASS
- `tf_stars_transactions_extended_media_extended_media` is `positioned === true` -- PASS

---

## Root: `tf_stars_transactions`

**TL type:** `StarsTransaction` (1 constructor: `starsTransaction`)
**Key:** `[id]` -- keyCols from `base[0]='id'`
**Tables:** 166

### Base columns

| Column | SQL type | Notes |
|--------|----------|-------|
| `account_id` | `BIGINT NOT NULL` | Tenant partition key |
| `id` | `TEXT NOT NULL` | Transaction ID (text-encoded) |
| `date` | `INTEGER NOT NULL` | Unix timestamp |
| `refund` | `BOOLEAN NOT NULL` | bool flag |
| `pending` | `BOOLEAN NOT NULL` | bool flag |
| `failed` | `BOOLEAN NOT NULL` | bool flag |
| `gift` | `BOOLEAN NOT NULL` | bool flag |
| `reaction` | `BOOLEAN NOT NULL` | bool flag |
| `stargift_upgrade` | `BOOLEAN NOT NULL` | bool flag |
| `business_transfer` | `BOOLEAN NOT NULL` | bool flag |
| `stargift_resale` | `BOOLEAN NOT NULL` | bool flag |
| `posts_search` | `BOOLEAN NOT NULL` | bool flag |
| `stargift_prepaid_upgrade` | `BOOLEAN NOT NULL` | bool flag |
| `stargift_drop_original_details` | `BOOLEAN NOT NULL` | bool flag |
| `phonegroup_message` | `BOOLEAN NOT NULL` | bool flag |
| `stargift_auction_bid` | `BOOLEAN NOT NULL` | bool flag |
| `offer` | `BOOLEAN NOT NULL` | bool flag |

**Boolean columns (14):** `refund`, `pending`, `failed`, `gift`, `reaction`, `stargift_upgrade`, `business_transfer`, `stargift_resale`, `posts_search`, `stargift_prepaid_upgrade`, `stargift_drop_original_details`, `phonegroup_message`, `stargift_auction_bid`, `offer`

### Child tables

| Child table | Role | Positioned | Notes |
|-------------|------|------------|-------|
| `tf_stars_transactions_amount` | 1:1 scalar | -- | `amount` (FK->StarsAmount): constructor, amount, nanos |
| `tf_stars_transactions_peer` | 1:1 peer | -- | `peer` (FK->StarsTransactionPeer): peer_type, peer_id |
| `tf_stars_transactions_title` | 1:1 scalar | -- | `title` TEXT |
| `tf_stars_transactions_description` | 1:1 scalar | -- | `description` TEXT |
| `tf_stars_transactions_photo` | 1:1 FK-target | -- | `photo` (FK->WebDocument) with nested attributes[], mask_coords |
| `tf_stars_transactions_transaction_date` | 1:1 scalar | -- | `transaction_date` INTEGER |
| `tf_stars_transactions_transaction_url` | 1:1 scalar | -- | `transaction_url` TEXT |
| `tf_stars_transactions_bot_payload` | 1:1 scalar | -- | `bot_payload` VARCHAR(255) |
| `tf_stars_transactions_msg_id` | 1:1 scalar | -- | `msg_id` INTEGER |
| `tf_stars_transactions_extended_media` | 1:N vector | YES | `extended_media` -- deep union decomposition spanning photos, documents, web_pages, polls, geo, story_items, todo_lists, message_medias |
| `tf_stars_transactions_subscription_period` | 1:1 scalar | -- | `subscription_period` INTEGER |
| `tf_stars_transactions_giveaway_post_id` | 1:1 scalar | -- | `giveaway_post_id` INTEGER |
| `tf_stars_transactions_stargift` | 1:1 FK-target | -- | `stargift` (FK->StarGift) with nested documents[], background, attributes[], peer_color |
| `tf_stars_transactions_floodskip_number` | 1:1 scalar | -- | `floodskip_number` INTEGER |
| `tf_stars_transactions_starref_commission_permille` | 1:1 scalar | -- | `starref_commission_permille` INTEGER |
| `tf_stars_transactions_starref_peer` | 1:1 peer | -- | `starref_peer` (peer_type TINYINT + peer_id BIGINT) |
| `tf_stars_transactions_starref_amount` | 1:1 FK-target | -- | `starref_amount` (FK->StarsAmount) |
| `tf_stars_transactions_paid_messages` | 1:1 scalar | -- | `paid_messages` INTEGER |
| `tf_stars_transactions_premium_gift_months` | 1:1 scalar | -- | `premium_gift_months` INTEGER |
| `tf_stars_transactions_ads_proceeds_from_date` | 1:1 scalar | -- | `ads_proceeds_from_date` INTEGER |
| `tf_stars_transactions_ads_proceeds_to_date` | 1:1 scalar | -- | `ads_proceeds_to_date` INTEGER |

#### `extended_media` deep tree (union decomposition)

`tf_stars_transactions_extended_media` carries a 1:N vector spanning multiple TL media union ctors. Nested children include:

- **Catalog-shared media nodes:** `tf_photos` (with sizes[], video_sizes[]), `tf_documents` (with attributes[], thumbs[], video_thumbs[]), `tf_web_pages` (with url child), `tf_story_items`, `tf_todo_lists`, `tf_message_medias`
- **Native children of extended_media union:** `tf_stars_transactions_extended_media_geo`, `tf_stars_transactions_extended_media_photo` (with attributes[], mask_coords), `tf_stars_transactions_extended_media_poll` (with question+entities, answers+text+entities, countries_iso2[]), `tf_stars_transactions_extended_media_results` (with results[], solution_entities[], message_media FK), `tf_stars_transactions_extended_media_game` (with outcome child), `tf_stars_transactions_extended_media_game_outcome`, `tf_stars_transactions_extended_media_channels[]`, `tf_stars_transactions_extended_media_countries_iso2[]`, `tf_stars_transactions_extended_media_winners[]`, `tf_stars_transactions_extended_media_completions[]`
- **Nested vector (post-fix):** `tf_stars_transactions_extended_media_extended_media` (POSITIONED) -- routes into catalog-shared `tf_message_medias`, `tf_photos`, `tf_documents`

**Note:** many catalog-shared nodes repeat across the tree because different union ctors independently recurse into the same FK targets.

### TL conformance

**Result:** `OK: all non-mask params consumed`

All non-flags-mask, non-nat params in the single `starsTransaction` constructor are consumed by base columns (account_id, id, date + 14 bools) or child tables. No unconsumed params.

### §14 deltas (informational)

§14 declares 19 child fact tables for `tf_stars_transactions`. The resolver produces 22 direct children (the extra 3 are `amount`, `peer`, and `starref_amount` which are FK-shaped base entries materialized as child fact tables per the resolver's base-routing logic, plus `starref_peer` which §14 lists as base but resolver routes as peer-shaped child). This is a resolver-level architectural choice; catalog and code win over §14 prose.

---

## Root: `tf_stars_subscriptions`

**TL type:** `StarsSubscription` (1 constructor: `starsSubscription`)
**Key:** `[id]` -- keyCols from `base[0]='id'` (id is TEXT, not BIGINT -- text-encoded subscription ID)
**Tables:** 8

### Base columns

| Column | SQL type | Notes |
|--------|----------|-------|
| `account_id` | `BIGINT NOT NULL` | Tenant partition key |
| `id` | `TEXT NOT NULL` | Subscription ID (text) |
| `peer_type` | `TINYINT NOT NULL` | Peer type discriminator |
| `peer_id` | `BIGINT NOT NULL` | Peer numeric ID |
| `until_date` | `INTEGER NOT NULL` | Unix timestamp |
| `canceled` | `BOOLEAN NOT NULL` | bool flag |
| `can_refulfill` | `BOOLEAN NOT NULL` | bool flag |
| `missing_balance` | `BOOLEAN NOT NULL` | bool flag |
| `bot_canceled` | `BOOLEAN NOT NULL` | bool flag |

**Boolean columns (4):** `canceled`, `can_refulfill`, `missing_balance`, `bot_canceled`

### Child tables

| Child table | Role | Positioned | Notes |
|-------------|------|------------|-------|
| `tf_stars_subscriptions_pricing` | 1:1 FK-target | -- | `pricing` (FK->StarsSubscriptionPricing): period, amount |
| `tf_stars_subscriptions_chat_invite_hash` | 1:1 scalar | -- | `chat_invite_hash` TEXT |
| `tf_stars_subscriptions_title` | 1:1 scalar | -- | `title` TEXT |
| `tf_stars_subscriptions_photo` | 1:1 FK-target | -- | `photo` (FK->WebDocument) with nested attributes[], mask_coords |
| `tf_stars_subscriptions_invoice_slug` | 1:1 scalar | -- | `invoice_slug` TEXT |

### TL conformance

**Result:** `OK: all non-mask params consumed`

All params in the single `starsSubscription` constructor are consumed. No unconsumed params.

### §14 deltas (informational)

§14 lists 4 children (chat_invite_hash, title, photo, invoice_slug). Resolver produces 5 direct children (same 4 plus `pricing` routed as 1:1 child from base entry). §14 lists `pricing` as base; resolver's `isFactShape` routes it to child table because it is an FK->StarsSubscriptionPricing shape. This is the standard resolver behavior for FK shapes. No structural delta.

---

## Root: `tf_saved_star_gifts`

**TL type:** `SavedStarGift` (1 constructor: `savedStarGift`)
**Key:** `[saved_star_gift_id]` -- synthetic key from `base[0]='id'` mapped to TL's `id` param
**Tables:** 41

### Base columns

| Column | SQL type | Notes |
|--------|----------|-------|
| `account_id` | `BIGINT NOT NULL` | Tenant partition key |
| `saved_star_gift_id` | `BIGINT NOT NULL` | Gift ID |
| `date` | `INTEGER NOT NULL` | Unix timestamp |
| `name_hidden` | `BOOLEAN NOT NULL` | bool flag |
| `unsaved` | `BOOLEAN NOT NULL` | bool flag |
| `refunded` | `BOOLEAN NOT NULL` | bool flag |
| `can_upgrade` | `BOOLEAN NOT NULL` | bool flag |
| `pinned_to_top` | `BOOLEAN NOT NULL` | bool flag |
| `upgrade_separate` | `BOOLEAN NOT NULL` | bool flag |

**Boolean columns (6):** `name_hidden`, `unsaved`, `refunded`, `can_upgrade`, `pinned_to_top`, `upgrade_separate`

### Child tables

| Child table | Role | Positioned | Notes |
|-------------|------|------------|-------|
| `tf_saved_star_gifts_gift` | 1:1 FK-target | -- | `gift` (FK->StarGift): limited, sold_out, birthday, etc. Deep nested children: documents[], background, attributes[], peer_color |
| `tf_saved_star_gifts_from_id` | 1:1 peer | -- | `from_id` (from_id_type TINYINT + from_id_id BIGINT) |
| `tf_saved_star_gifts_message` | 1:1 FK-target | -- | `message` (FK->TextWithEntities): text + entities[] |
| `tf_saved_star_gifts_msg_id` | 1:1 scalar | -- | `msg_id` INTEGER |
| `tf_saved_star_gifts_saved_id` | 1:1 scalar | -- | `saved_id` BIGINT |
| `tf_saved_star_gifts_convert_stars` | 1:1 scalar | -- | `convert_stars` BIGINT |
| `tf_saved_star_gifts_upgrade_stars` | 1:1 scalar | -- | `upgrade_stars` BIGINT |
| `tf_saved_star_gifts_can_export_at` | 1:1 scalar | -- | `can_export_at` INTEGER |
| `tf_saved_star_gifts_transfer_stars` | 1:1 scalar | -- | `transfer_stars` BIGINT |
| `tf_saved_star_gifts_can_transfer_at` | 1:1 scalar | -- | `can_transfer_at` INTEGER |
| `tf_saved_star_gifts_can_resell_at` | 1:1 scalar | -- | `can_resell_at` INTEGER |
| `tf_saved_star_gifts_collection_id` | 1:N vector | YES | `collection_id` vector of INTEGER |
| `tf_saved_star_gifts_prepaid_upgrade_hash` | 1:1 scalar | -- | `prepaid_upgrade_hash` TEXT |
| `tf_saved_star_gifts_drop_original_details_stars` | 1:1 scalar | -- | `drop_original_details_stars` BIGINT |
| `tf_saved_star_gifts_gift_num` | 1:1 scalar | -- | `gift_num` INTEGER |
| `tf_saved_star_gifts_can_craft_at` | 1:1 scalar | -- | `can_craft_at` INTEGER |

#### `gift` deep tree

`tf_saved_star_gifts_gift` recurses into `StarGift` FK target, materializing:
- `tf_documents` (catalog-shared) with attributes[], thumbs[], video_thumbs[]
- `tf_saved_star_gifts_gift_background` -- center_color, edge_color, text_color
- `tf_saved_star_gifts_gift_attributes` [POSITIONED] -- crafted, name, etc., with nested attributes_document, rarity, message+entities
- `tf_saved_star_gifts_gift_resell_amount` [POSITIONED] -- constructor, amount, nanos
- `tf_saved_star_gifts_gift_peer_color` -- constructor, color, background_emoji_id, collectible_id, with colors[] and dark_colors[] vectors

### TL conformance

**Result:** `OK: all non-mask params consumed`

All params in the single `savedStarGift` constructor are consumed. No unconsumed params.

### §14 deltas (informational)

§14 declares 15 children. Resolver produces 16 direct children. The extra child is `gift` materialized as a 1:1 child fact table (FK->StarGift) whereas §14 lists it as a single base column -- same resolver behavior as `pricing` above: FK shapes route through `resolveChildField`. §14 delta: minor, catalog/code level architectural detail.

---

## Root: `tf_phone_calls`

**TL type:** `PhoneCall` (6 constructors: `phoneCallEmpty`, `phoneCallWaiting`, `phoneCallRequested`, `phoneCallAccepted`, `phoneCall`, `phoneCallDiscarded`)
**Key:** `[id]`
**Tables:** 4

### Base columns

| Column | SQL type | Notes |
|--------|----------|-------|
| `account_id` | `BIGINT NOT NULL` | Tenant partition key |
| `id` | `BIGINT NOT NULL` | Phone call ID |
| `constructor` | `TEXT NOT NULL` | Discriminator (6 ctors) |
| `access_hash` | `BIGINT NOT NULL` | |
| `date` | `INTEGER NOT NULL` | Unix timestamp |
| `admin_id` | `BIGINT NOT NULL` | |
| `participant_id` | `BIGINT NOT NULL` | |
| `video` | `BOOLEAN NOT NULL` | bool flag |

**Boolean columns (1):** `video`

### Child tables

| Child table | Role | Positioned | Notes |
|-------------|------|------------|-------|
| `tf_phone_calls_protocol` | 1:1 FK-target | -- | `protocol` (FK->PhoneCallProtocol): udp_p2p, udp_reflector, min_layer, max_layer + library_versions[] |
| `tf_phone_calls_receive_date` | 1:1 scalar | -- | `receive_date` INTEGER (phoneCallWaiting/phoneCall ctor only) |

### TL conformance

**Result:** 13 unconsumed params (catalog-intended omissions)

These are constructor-specific params from `phoneCallRequested` (g_a_hash), `phoneCallAccepted` (g_b), `phoneCall` (p2p_allowed, conference_supported, g_a_or_b, key_fingerprint, connections, start_date, custom_parameters), and `phoneCallDiscarded` (need_rating, need_debug, reason, duration). All are ctor-specific ephemeral/session params that the catalog intentionally omits (session-level crypto params, call-state params, transient discard reasons). **Severity: informational (catalog curation, not a resolver defect).**

### §14 deltas (informational)

§14 declares base cols: id, access_hash, date, admin_id, participant_id, protocol (FK). §14 declares 1 child: receive_date. Resolver produces 1 extra child: `protocol` routed as 1:1 child FK table (§14 lists it as base; same FK-as-child routing pattern). §14 lists `video` as bool flag only -- resolver matches. All §14 structural children present. No structural deltas.

---

## Root: `tf_group_calls`

**TL type:** `GroupCall` (2 constructors: `groupCallDiscarded`, `groupCall`)
**Key:** `[id]`
**Tables:** 1

### Base columns

| Column | SQL type | Notes |
|--------|----------|-------|
| `account_id` | `BIGINT NOT NULL` | Tenant partition key |
| `id` | `BIGINT NOT NULL` | Group call ID |
| `constructor` | `TEXT NOT NULL` | Discriminator (2 ctors) |
| `access_hash` | `BIGINT NOT NULL` | |
| `duration` | `INTEGER NOT NULL` | |

**Boolean columns (0):** none

### Child tables

None. §14 and resolver agree: `tf_group_calls` has no child fact tables.

### TL conformance

**Result:** 24 unconsumed params (catalog-intended omissions)

All 24 are `groupCall`-ctor-specific params: 13 true flags (`join_muted`, `can_change_join_muted`, `join_date_asc`, `schedule_start_subscribed`, `can_start_video`, `record_video_active`, `rtmp_stream`, `listeners_hidden`, `conference`, `creator`, `messages_enabled`, `can_change_messages_enabled`, `min`) and 11 scalars/refs (`participants_count`, `title`, `stream_dc_id`, `record_start_date`, `schedule_date`, `unmuted_video_count`, `unmuted_video_limit`, `version`, `invite_link`, `send_paid_messages_stars`, `default_send_as`/Peer). All are catalog-curated omissions -- runtime state that does not belong to the static mirror. **Severity: informational (catalog curation, not a resolver defect).**

### §14 deltas (informational)

§14 declares base cols: id, access_hash, duration. §14 declares no children. Resolver matches exactly. No deltas.

---

## Fix log

| Commit | File:Line | Defect | Description |
|--------|-----------|--------|-------------|
| `5f460dee` | `MirrorTableResolver.php:80-99` | Scalar children silently dropped | The merged base+children loop in `resolveTable()` gated every child entry behind `isFactShape()`, so scalar/peer-shaped catalog children (e.g. `tf_phone_calls_receive_date`, `tf_stars_subscriptions_pricing`) were never materialized. Fix: split into two loops -- base entries keep the `isFactShape()` gate (scalars stay parent columns); children entries route all non-key shapes through `resolveChildField`. |
| `5f460dee` | `MirrorTableResolver.php:420-426, 469-482` | Vector nested positioned lost to earlier bare-ref | In `unionParams()`, the `hasChild` dedup guard and lack of promotion logic meant a bare-ref (e.g. `messageMediaInvoice` referencing `messageMediaInvoice.extended_media`) won over a later vector ctor (e.g. `messageMediaPaidMedia.extended_media`), losing the POSITIONED flag. Fix: replaced both `hasChild`-guarded appends with `mergeChild()` which detects same-table-name children and promotes non-positioned to positioned when a positioned variant exists. |

Regression guards proven in `inv_stars2.php`:
- `tf_phone_calls_receive_date` present with `keyColumns=['id']` -- PASS
- `tf_stars_transactions_extended_media_extended_media` is `positioned === true` -- PASS

**Gates green:** phpunit 44/44 (1839 assertions); phpstan level 5 -- 0 errors

---

## §14 cross-family architectural notes (informational)

1. **FK-as-child routing pattern:** §14 lists some fields as base columns (e.g. `protocol`, `pricing`, `gift`), but the resolver treats FK-shaped entries (`FK->TypeName`) as child fact tables. This is by design -- the resolver never stores a bare foreign key integer on the parent; it always materializes the target shape into its own child table. This accounts for the 1-extra-child count in `tf_stars_subscriptions`, `tf_saved_star_gifts`, and `tf_phone_calls`.

2. **`extended_media` union expansion:** §14 lists 1 direct 1:N vector child (`tf_stars_transactions_extended_media`). The resolver produces the deep nested tree (166 total tables for the root) because the TL union decomposition materializes every distinct sub-shape across all non-empty ctors of `MessageMedia.extended_media`. This is the resolver's core NF5 §12/§14 child-expansion behavior.

3. **phone_calls + group_calls unconsumed params (37 total):** These are catalog-intended omissions of ctor-specific ephemeral/runtime params (crypto handshake bytes, call state flags, join/participant metadata). They are not resolver defects -- the catalog deliberately does not declare them as mirror fact columns.
