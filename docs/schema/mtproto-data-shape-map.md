# MTProto data-shape map — method to shape, and what each shape needs alongside

Derived from `src/Schema/schema/sources/api_full.tl` (the wire truth, layer 227)
and `src/Schema/schema/methods-mtproto.json` (823 methods, layer 229 catalog).

## How to use this document

1. Given a method, find its return type in section 2.
2. That type CONTAINS entity types. Section 3 lists, for each of those entities, every
   field that is a REFERENCE to another entity rather than a stored value.
3. Section 4 gives the fetcher for each referenced type.
4. If the fetcher is FETCHABLE and the row is absent, the ingestor must REQUEST it before
   writing the parent row. A row whose reference has no companion cannot be joined later.

That last point is the failure the owner named: "lacking a dependant data might lead to
query fail".

To re-derive any row: split the .tl on `;` (never on `=` — a field may contain one) and
extract fields with `([a-z_0-9]+):(flags\d*\.\d+\?)?([A-Za-z_][A-Za-z_0-9<>.]*)`.

## 1. Scale

- constructors carrying at least one reference: see the CORRECTION below — the figure
  originally given here (201) does NOT reproduce and was definition-dependent.

**CORRECTION to the count (found by re-verification, 2026-10-01).** This section
originally read "constructors carrying at least one reference: **201**". Re-running the
recipe under other reasonable definitions does not reproduce it:

| definition of "an entity type" | count |
|---|---|
| non-primitive types only, `Vector` excluded | 553 |
| generics allowed | 655 |
| flag-conditional references only | **209** |
| hand-listed entity set used in this document | 191 |

The recipe parsed 1303 constructors but never defined what counts as an entity type, so
the headline number was an artifact of one unstated choice. 201 is not reproducible and is
withdrawn. The three entity tables in section 3 are unaffected: they are exact, because
they name their constructors rather than counting them.

For the count the models stage actually needs, see
`ingest-stage-1-record.md` — it also corrects the companion-table denominator from 36 to
**35** (36 `Schema::create('tf_messages…')` calls includes `tf_messages` itself).

## 2. Method -> return type

| method | return | callable | catalog id |
|---|---|---|---|
| `messages.getHistory` | `messages.Messages` | True | `0x4423e6c5` |
| `messages.getDialogs` | `messages.Dialogs` | True | `0xa0f4cb4f` |
| `messages.getMessages` | `messages.Messages` | True | `0x63c66506` |
| `messages.search` | `messages.Messages` | True | `0x29ee847a` |
| `users.getUsers` | `Vector<User>` | True | `0x0d91a548` |
| `users.getFullUser` | `users.UserFull` | True | `0xb60f5918` |
| `contacts.resolveUsername` | `contacts.ResolvedPeer` | True | `0x725afbbc` |
| `contacts.getStatuses` | `Vector<ContactStatus>` | True | `0xc4a353ee` |
| `updates.getState` | `updates.State` | True | `0xedd4882a` |
| `updates.getDifference` | `updates.Difference` | True | `0x19c2f763` |
| `updates.getChannelDifference` | `updates.ChannelDifference` | True | `0x03173d78` |
| `help.getConfig` | `Config` | True | `0xc4f9186b` |

`chats.getChats` is absent from the 823-method catalog entirely, so there is no method by
that name to hydrate a bare chat id.

## 3. References, by entity (the dependency surface)

| entity | fields | references |
|---|---|---|
| `message` | 49 | `from_id`->Peer, `peer_id`->Peer, `saved_peer_id`->Peer, `fwd_from`->MessageFwdHeader, `guestchat_via_from`->Peer, `reply_to`->MessageReplyHeader, `media`->MessageMedia |
| `updateNewMessage` | 3 | `message`->Message |
| `updateNewChannelMessage` | 3 | `message`->Message |
| `updateEditMessage` | 3 | `message`->Message |
| `dialog` | 16 | `peer`->Peer |
| `messageFwdHeader` | 13 | `from_id`->Peer, `saved_from_peer`->Peer, `saved_from_id`->Peer |
| `messageReplyHeader` | 14 | `reply_to_peer_id`->Peer, `reply_from`->MessageFwdHeader, `reply_media`->MessageMedia |
| `chatFull` | 20 | `participants`->ChatParticipants, `chat_photo`->Photo, `groupcall_default_join_as`->Peer |
| `channelFull` | 67 | `chat_photo`->Photo, `groupcall_default_join_as`->Peer, `default_send_as`->Peer, `wallpaper`->WallPaper |

## 4. Fetcher for each referenced type

| referenced type | fetcher |
|---|---|
| `Channel` | channels.getChannels |
| `ChannelFull` | channels.getFullChannel |
| `Chat` | chats.getChats (ABSENT from catalog) |
| `ChatFull` | chats.getFullChat |
| `ChatParticipants` | embedded in ChatFull; absent members -> channels.getParticipants |
| `Dialog` | messages.getDialogs (the source itself) |
| `Document` | embedded; upload.getFile |
| `Message` | messages.getMessages or messages.getHistory |
| `MessageAction` | embedded |
| `MessageEntity` | embedded (byte offsets into message text) |
| `MessageFwdHeader` | embedded; its own Peer still needs resolution |
| `MessageMedia` | embedded; Document/Photo ids need upload.getFile |
| `MessageReplyHeader` | embedded; reply_to_msg_id -> messages.getMessages |
| `Peer` | users.getUsers / channels.getChannels — access_hash obtainable only via messages.getDialogs |
| `Photo` | embedded; sizes -> upload.getFile |
| `SponsoredMessage` | embedded |
| `StoryItem` | embedded; absent story -> stories.getStoriesByID |
| `User` | users.getUsers |
| `UserFull` | users.getFullUser |
| `WallPaper` | embedded |
## 5. FAILURE MODES — where a query fails on a missing dependent row

**CORRECTION — the first draft of this section was wrong and the error is preserved
here deliberately.**

The draft claimed `tf_messages` was missing all seven reference fields, and therefore
that a message row "cannot be joined" to its author, reply target, forward source or
media. That was measured from `SHOW COLUMNS tf_messages` alone, which returns 22
columns, and it inferred the shape from the columns of ONE table. It was wrong.

The schema does not flatten a `Message` into one wide row. It stores the scalar fields on
`tf_messages` and **every optional or non-scalar field on its own 1:1 companion table**,
keyed `(account_id, id)`. `src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php`
declares **36** such tables, including exactly the reference fields the draft called absent:

| wire field | stored in | declared at |
|---|---|---|
| `from_id:Peer` | `tf_messages_from_id` (`from_id_type`, `from_id_id`) | migration:126-131 |
| `saved_peer_id:Peer` | `tf_messages_saved_peer_id` | migration:148-153 |
| `fwd_from:MessageFwdHeader` | `tf_messages_fwd_from` — "flattened 1:1" per the comment | migration:156-163 |
| `reply_to:MessageReplyHeader` | `tf_messages_reply_to` | migration (dropIfExists at :74) |
| `guestchat_via_from:Peer` | `tf_messages_guestchat_via_from` | declared |
| `via_bot_id`, `post_author`, `grouped_id`, `edit_date`, `views`, `forwards`, `replies`, `reactions`, `restriction_reason`, `effect`, `factcheck`, `ttl_period` | one companion table each | declared |

plus a mirrored `tf_messages_service*` family for service messages, and
`tf_messages_reply_markup_rows` / `_buttons` for nested keyboard markup.

**So the shape WAS built. The real gap is elsewhere, and it is behavioural, not structural.**

### 5.1 The genuine join failures

| relation | status | reason |
|---|---|---|
| message -> author | **CAN JOIN**, two-hop | `tf_messages_from_id.from_id_type` + `from_id_id` -> the peer spine. Requires the type discriminator to select the right target table. |
| message -> conversation | **CAN JOIN** | `tf_messages.peer_id` + `peer_type` on the parent row. |
| message -> reply target | **CAN, but only if the referenced message is also present** | `tf_messages_reply_to` carries `reply_to_msg_id`; that row exists only if the target message was itself ingested. A reply into a not-yet-crawled range is a dangling reference. |
| message -> forward source | **CAN, same caveat** | `tf_messages_fwd_from` is the flattened header; its own `from_id_type`/`from_id_id` needs the peer spine. |
| message -> media | **CAN, via a sibling table family** | `2026_09_14_200012_create_tf_message_medias_tables.php` declares the media tables; keyed by the same `(account_id, id)`. |
| dialog -> peer | **CAN JOIN** | `tf_dialogs` keys on `(account_id, peer_type, peer_id)` (2026_09_14_200004_create_tf_dialogs_tables.php:51-89), the same type-discriminator pattern as messages. |
| chat -> participants | **CAN JOIN** | modelled: `tf_channel_participants`, `_peer`, `_inviter`, `_kicked_by`, `_promoted_by`, `_rank`, `_subscription_until_date`. |

Both previously-UNVERIFIED rows are now resolved from the migrations, not assumed.

### 5.2 The failure mode that remains, and it is the one that matters

`reply_to` and `fwd_from` are the only references whose target is **another message**,
and messages are paged newest-to-oldest with `messages.getHistory`. A backfill that has
crawled messages 12400-12413 stores a reply row pointing at, say, message 9800 — which
does not exist yet. The row is written successfully; the JOIN fails later, at read time,
whenever the host asks for the thread.

That is precisely the owner's warning — "updates may come but lacking a dependant data
might
lead to query fail" — and it is an **ordering** problem, not a schema problem. The fix is
in ingest: when a `reply_to_msg_id` or forward-source id is not in the mirror, the
ingestor must REQUEST that message (this is the "request for any missing data while you
ingest"), or record the reference as unresolved so the join can be retried once the range
is covered.

### 5.3 The allowlist makes the first hop impossible

The six live-eligible methods are `users.getUsers`, `users.getFullUser`,
`help.getConfig`, `help.getNearestDc`, `auth.getPassword`, `account.getPassword`.

Both `users.*` methods take `InputUser`, which **carries `access_hash` as an input**. They
can only re-verify a hash the caller already holds. Every source of a peer's `access_hash`
— `messages.getDialogs`, `messages.getHistory`, `updates.getState` — is outside the
allowlist. The chain is circular: an `access_hash` cannot be obtained without an allowed
method, and an allowed method cannot be used without one.

This is why V1.1 is structurally unprovable under the current policy, as opposed to merely
untried. Widening the allowlist trades a skipped test for a possible account, so the policy
is deliberately left closed and the gap recorded here instead.

## 6. What this map tells the models stage

Given the correction above, the models stage is **NOT** a build-from-scratch. What it
needs to establish is:

1. **Which companion tables have no model.** The Eloquent side is
   `src/Teleframe/Mirror/Models/`; the 36 companion tables are declared in migrations.
   Whether a model (and therefore a relation) exists per companion table is the thing to
   verify.
2. **The peer spine.** `from_id_type`/`from_id_id` and `peer_type`/`peer_id` are type
   discriminators, not foreign keys. A relation cannot be expressed as an FK unless the
   target is known at write time.
3. **The ordering rule for message-to-message references**, which is the only real join
   failure left, and belongs in ingest rather than in the schema.
4. **No new table is implied by anything found so far.** The schema is more complete than
   this document's first draft claimed; the correction is recorded above rather than
   quietly deleted.
