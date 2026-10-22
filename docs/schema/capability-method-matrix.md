# Capability → method matrix — what each Version One capability needs, and what blocks it

The CAPABILITY dimension, built on top of
[`mtproto-data-shape-map.md`](./mtproto-data-shape-map.md) (committed `ea613a63`, DONE). That
document owns the **shape** question — method → return type → referenced entity → fetcher. It is
not edited or duplicated here. This document answers the **capability** question the shape map
cannot: *for each of the six Version One milestones, which methods must the ingestor be able to
call, in what order, and what exactly prevents it today.*

Read this when you are about to build the ingest stage and want to "request for any missing data
while you ingest" (the owner's words, `.brain/verbatim-goals.md`, Version One readiness section).
This table is that request list, per capability, and it says plainly which of the six requests are
impossible under the current live-endpoint policy.

## Sources

| source | what it gives | verified how |
|---|---|---|
| `src/Schema/schema/methods-mtproto.json` | 823 methods; `{id, params, return, errors, wire:{callable, reason}}` | parsed directly |
| `src/Teleframe/Warm/LiveEndpointPolicy.php` | the allowlist — exactly **six** entries | read whole file |
| `src/Schema/schema/sources/api_full.tl` | the wire truth, layer 227 | `inputUser#f21158c6` at `:22` |
| `.brain/verbatim-goals.md` | the V1.1–V1.6 readiness table | read |

Nothing here was executed against Telegram. No live RPC, no warm lane, no database, no
credentials. `wire.callable` values are read from the catalog's own flag, never assumed.

## 0. The policy in one paragraph, because every row below depends on it

`LiveEndpointPolicy::LIVE_SAFE` (`src/Teleframe/Warm/LiveEndpointPolicy.php:75-95`) contains
exactly six method names:

| # | method | reason recorded at | in the 823-method catalog? |
|---|---|---|---|
| 1 | `users.getUsers` | `:76-77` | yes — `0x0d91a548`, `callable=true` |
| 2 | `users.getFullUser` | `:79-80` | yes — `0xb60f5918`, `callable=true` |
| 3 | `help.getConfig` | `:82-83` | yes — `0xc4f9186b`, `callable=true` |
| 4 | `help.getNearestDc` | `:85-86` | yes — `0x1fb33026`, `callable=true` |
| 5 | `auth.getPassword` | `:88-90` | **NO — absent from the catalog** |
| 6 | `account.getPassword` | `:92-94` | yes — `0x548a30f5`, `callable=true` |

The policy is deny-by-default: `refusalReason()` returns a non-null string for anything not in
`LIVE_SAFE` (`:135-141`), and independently refuses anything whose verb starts with a member of
`DESTRUCTIVE_VERBS` (`:123-133`). That verb list (`:58-65`) contains `read`, `send`, `set`,
`sign`, `upload`, `edit`, `delete` — which is why `messages.getHistory`, `messages.getDialogs`,
`messages.readHistory` and `upload.getFile` are all refused **twice over**: not in the allowlist
*and* verb-denied.

`auth.getPassword` is a **defect in the allowlist itself, not a gap in the catalog**: the entry
names a method that cannot be called because it is not in the catalog at all, so the entry can
never do what its docblock claims. Its own comment at `:89-90` says it "does not begin the flow
and cannot invalidate the session", which is true of a method that does not exist. Already
recorded at `.brain/evidence/v1-live-reachability.md:50`. Left unfixed — this lane may not alter the
allowlist.

## 1. The five blocking categories — used verbatim, never conflated

The owner was explicit that conflating these produced a wrong finding earlier in the project
(`.brain/verbatim-goals.md`, Cycle 38). Each row below uses exactly one, and the definitions are
narrow on purpose:

| category | definition — what it means and what it does NOT mean |
|---|---|
| **NOT-CATALOGUED** | The method has no entry in `methods-mtproto.json`. Nothing can call it, regardless of policy. Widening the allowlist does **not** help. |
| **NOT-CALLABLE** | In the catalog, but `wire.callable == false`. The catalog records why. Widening the allowlist does **not** help — the wire cannot carry it. |
| **NOT-ALLOWLISTED** | In the catalog and callable, but `LiveEndpointPolicy::refusalReason()` returns non-null. **Only** a package-source change to `LIVE_SAFE` helps. No configuration, no deployment. |
| **NEEDS-HUMAN** | The method is in the catalog and callable, and would be callable if allowlisted — but cannot complete without an interactive input only a person at a keyboard can supply (an SMS/app code, a 2FA password). The blocker survives the allowlist widening. |
| **NEEDS-DEPLOYMENT** | The method is fine and the call would work; what is missing is an external service or binary in the environment. No code change fixes it. |

A row may have more than one blocker. Where it does, they are listed in this order —
NOT-CATALOGUED, NOT-CALLABLE, NOT-ALLOWLISTED, NEEDS-HUMAN, NEEDS-DEPLOYMENT — because a blocker
upstream of this list makes a later one irrelevant.

## 2. The matrix

`in allowlist` = `LiveEndpointPolicy::isLiveSafe()` would return true. Catalog ids are the
`id` field of `methods-mtproto.json`.

### V1.1 — Notification / logger core (topics as log levels, queue-shaped, limits)

V1.1 needs to resolve *who* to notify and *what* to name them, which is the peer spine. The
peer spine's only fetcher is `users.getUsers`.

| method | catalog id | callable | in allowlist | why V1.1 needs it | blocked by |
|---|---|---|---|---|---|
| `users.getUsers` | `0x0d91a548` | true | **yes** | Turn a `user_id` from an update into a name/id pair the log row can join to. This is V1.1's *only* hydration call. | — |
| `users.getFullUser` | `0xb60f5918` | true | **yes** | The extended profile (bio, bot flag) for the notification target. | — |
| `help.getConfig` | `0xc4f9186b` | true | **yes** | Confirms the session is live at all; without it nothing downstream is meaningful. | — |
| `help.getNearestDc` | `0x1fb33026` | true | **yes** | Which DC to talk to before any of the above. | — |
| `auth.getPassword` | *absent* | — | yes (unenforceable) | The allowlist claims it; the catalog cannot supply it. | **NOT-CATALOGUED** |
| `account.getPassword` | `0x548a30f5` | true | **yes** | Two-step state; `params=[]`, so it is genuinely callable. | — |
| `messages.getDialogs` | `0xa0f4cb4f` | true | no | **The only live source of a peer's `access_hash`.** | **NOT-ALLOWLISTED** (verb `getDialogs` unmatched, denied by `:135-141`) |
| `messages.getHistory` | `0x4423e6c5` | true | no | Second live source of `access_hash`, per message `from_id`. | **NOT-ALLOWLISTED** (verb list has no `getHistory`, denied by `:135-141`) |
| `updates.getState` | `0xedd4882a` | true | no | Third live source: `users` state carries `access_hash`. | **NOT-ALLOWLISTED** |
| `contacts.resolveUsername` | `0x725afbbc` | true | no | Resolves a username to a peer, returning the `access_hash` needed to use it. | **NOT-ALLOWLISTED** |
| `chats.getChats` | *absent* | — | no | Fetcher for a bare `Chat` id (shape map §4). | **NOT-CATALOGUED** |
| `messages.getChats` | `0x49e9528f` | true | no | **The same fetch, under a different name** — `params=[{name:"id", type:"Vector<long>"}]`, `return=messages.Chats`. The shape map lists only the absent spelling. | **NOT-ALLOWLISTED** |

**Ordered request list — V1.1.** On receiving an update whose `from_id` user row is absent:

1. `help.getNearestDc` — establish DC. (allowlisted)
2. `help.getConfig` — establish the session is live. (allowlisted)
3. `users.getUsers([inputUser(user_id, access_hash)])` — needs an `access_hash` **the ingestor
   does not have**. (allowlisted, circular at step 3)
4. `messages.getDialogs(limit, ...)` — the first method that would *supply* the hash. (**step 3's
   missing input**)
5. `users.getFullUser(inputUser(...))` — extended profile, once step 3 can run.

**Step 3 cannot execute before step 4, and step 4 is not allowed. The list has no valid start.**

### V1.2 — Telegram as permanent backup storage (verbatim names ftp / rsync / s3; only s3 is implemented, recorded as "4-GiB chunks")

V1.2 is a *write* capability: it stores chunks into Telegram and reads them back. It needs the
document fetcher to round-trip, and a session.

| method | catalog id | callable | in allowlist | why V1.2 needs it | blocked by |
|---|---|---|---|---|---|
| `upload.getFile` | `0xbe5335be` | true | no | Read a stored chunk back out. `TelegramVault` already calls it in the part loop (`src/Teleframe/Backup/TelegramVault.php:935`). | **NOT-ALLOWLISTED** — and `upload` is in `DESTRUCTIVE_VERBS` (`:63`), so verb-denied independently |
| `messages.search` | `0x29ee847a` | true | no | Locate a stored chunk message by name. Named at `TelegramVault.php:29`. | **NOT-ALLOWLISTED** |
| `messages.getMessages` | `0x63c66506` | true | no | Resolve a chunk message id to its `Document`. | **NOT-ALLOWLISTED** |
| `users.getUsers` | `0x0d91a548` | true | **yes** | Resolve the account's own id for a chunk-addressing path. | — |
| `messages.getHistory` | `0x4423e6c5` | true | no | Walk the "Saved Messages" conversation that holds chunks. | **NOT-ALLOWLISTED** |

**Ordered request list — V1.2.** To restore one chunk:

1. `messages.search(q="<chunk name hash>")` → find the chunk message id.
2. `messages.getMessages([id])` → obtain its `Document` (`location`, size, mime).
3. `upload.getFile(location, offset=0)` → stream part 1.
4. `upload.getFile(location, offset=<next>)` → loop until the part is short
   (`TelegramVault.php:916` is this loop).
5. Verify the AEAD tag locally; only then place the plaintext.

**Every step except a hypothetical step 0 is NOT-ALLOWLISTED, and steps 1–4 are all verb-denied
(`upload`, and none of the others is in the allowlist). V1.2's *read* half is structurally
impossible under the current policy. Note that V1.2's `s3` driver does not touch
Telegram at all and is unaffected — the blocker is specific to "Telegram as the storage".**

### V1.3 — Laravel DB auto-dump + hash-based backup

V1.3 needs no Telegram method to *dump* the DB. It needs Telegram only to *store* the result,
which lands it on V1.2's blockers.

| method | catalog id | callable | in allowlist | why V1.3 needs it | blocked by |
|---|---|---|---|---|---|
| *(no Telegram method)* | — | — | — | The DB dump itself is local: worker shipped per `.brain/verbatim-goals.md` (commits `2350ff61`, `9c44f927`, `c536d77b`). | — |
| *(no Telegram method)* | — | — | — | Chunk sizing is `ChunkPlanner::PART_SIZE = 524288` (512 KiB, `src/Teleframe/Store/ChunkPlanner.php:47`), with `PREMIUM_CAP = 4194304000` bytes = **3.90625 GiB** (`:55`) and `FREE_CAP` = 1.953125 GiB (`:53`, the default). Note the recorded "4-GiB chunks" is 4×10⁹ bytes, not 4 GiB. | — |
| `upload.getFile` | `0xbe5335be` | true | no | Only if the dump is stored *in* Telegram. | **NOT-ALLOWLISTED** + verb-denied |
| `messages.search` | `0x29ee847a` | true | no | Same, for lookup. | **NOT-ALLOWLISTED** |
| `users.getUsers` | `0x0d91a548` | true | **yes** | Session liveness check before a dump run. | — |
| `help.getConfig` | `0xc4f9186b` | true | **yes** | Canonical "is the session alive" call (`LiveEndpointPolicy.php:83`). | — |

**Ordered request list — V1.3.** 1. `help.getConfig` (liveness). 2. dump the DB locally. 3. chunk
the dump — 512 KiB parts (`ChunkPlanner::PART_SIZE`, `src/Teleframe/Store/ChunkPlanner.php:47`)
under a 3.90625 GiB premium cap (`:55`). 4. write chunks to the configured driver. 5. `upload.getFile`
/ `messages.search` **only** when the driver is `telegram` — steps 5+ are NOT-ALLOWLISTED.

**V1.3 is the one capability whose Telegram-independent half is fully available.** Two independent
blockers remain, and neither is a method blocker: the default dumper stays `'null'` so a stock
install cannot dump a production DB (`.brain/verbatim-goals.md`, V1.3 row), and the Telegram driver
half is V1.2's blocker.

### V1.4 — mtproto-all (whatever Telegram activity is needed, user and bot)

V1.4 is enforced by a test, not by a live call — `vendor/bin/phpunit
tests/Core/Methods/UncurlatedMethodResolutionTest.php` → `OK (13 tests, 89 assertions)` per
`.brain/verbatim-goals.md`. So V1.4 needs **no allowlisted method at all**.

| method | catalog id | callable | in allowlist | why V1.4 needs it | blocked by |
|---|---|---|---|---|---|
| *(all 823)* | various | 787 of 823 `callable=true` | no (6 of 823) | V1.4's requirement is *encodability/serialisability*, proved offline by the 36-dial / 34-serialise rule. | — |
| `help.getConfig` | `0xc4f9186b` | true | **yes** | Optional: the live sanity check, not the requirement. | — |

**Ordered request list — V1.4.** Empty. The requirement is discharged offline.

**V1.4 is provably satisfiable today and is the strongest capability in the set.** 36 methods are
`callable=false`; the catalog's own `wire.reason` distinguishes three causes — declared by the
catalog but absent from the wire mirror (`communities.*`, `ephemeral.*`), an MTProto *service*
request not an RPC (`destroy_auth_key`, `req_pq`), or an RPC *envelope* not an endpoint
(`invokeWithLayer` and 10 siblings, count 11 per the catalog's `wire.envelope_count`). These are
correctly non-callable and are **NOT-CALLABLE by design, not a defect**.

### V1.5 — Telegram Passport (Laravel users log in with it when configured)

| method | catalog id | callable | in allowlist | why V1.5 needs it | blocked by |
|---|---|---|---|---|---|
| `auth.sendCode` | `0xa677244f` | true | no | Begins login; returns `auth.SentCode` whose `phone_code` the human must type. | **NEEDS-HUMAN** — and **NOT-ALLOWLISTED** (verb `send`, `DESTRUCTIVE_VERBS:63`) |
| `auth.signIn` | `0x8d52a951` | true | no | Exchanges phone code + phone number hash for `auth.Authorization`. | **NOT-ALLOWLISTED** (verb `sign`, `DESTRUCTIVE_VERBS:63`) |
| `auth.checkPassword` | `0xd18b4d16` | true | no | The 2FA step when `phone_code_settings.need_password` is set. | **NOT-ALLOWLISTED** (verb `check` is NOT in the list; denied by `:135-141` only) |
| `auth.setPassportDataErrors` | — | — | no | The *other* half of Passport: reporting field errors. The `errors` array is now BUILT by `PassportErrorReporter::botApiErrors()` from the document coverage table (`9c628c95`), and the MTProto twin `users.setSecureValueErrors#90c894b5` is dispatched by `PassportErrorReporter::report()`. The **HTTP POST** is still absent: this package's only wire seam is MTProto, so a host posts the array itself. (Note the name is also wrong — there is no `auth.setPassportDataErrors`; the Bot API method is `setPassportDataErrors`.) | **NOT-ALLOWLISTED** |
| `account.getPassword` | `0x548a30f5` | true | **yes** | Reads two-step state. Genuinely callable, `params=[]`. | — |
| `auth.getPassword` | *absent* | — | yes (unenforceable) | Allowlist entry naming a non-existent method. | **NOT-CATALOGUED** |

**Ordered request list — V1.5.** 1. `auth.sendCode(phone_number, api_id, api_hash)` →
**a human reads `phone_code` from Telegram and types it**. 2. `auth.signIn(phone_number,
phone_code_hash, phone_code)` → `auth.Authorization`. 3. `auth.checkPassword(password)` if 2FA is
set → **a human types a password**. 4. `setPassportDataErrors(...)` to report Passport
field errors — the `errors` array is built by `PassportErrorReporter`, the POST
is the host's.

**Steps 1–4 are all NOT-ALLOWLISTED; steps 1 and 3 additionally NEEDS-HUMAN.** Widening the
allowlist would not make V1.5 work, because step 1's output has to be read by a person. This is
also the recorded live finding: `GET /api/passport/start` returns HTTP 200 with a live nonce but
`"bot_id":0` and `"telegram_url":null`, because Passport is a *bot* feature and nothing in the app
supplies a bot id (`.brain/verbatim-goals.md`, "Passport hot test" section;
`.brain/evidence/passport-hot-test-2026-10-01.md`).

### V1.6 — Bot/user app framework (AI answering, observers, Redis hot reload)

| method | catalog id | callable | in allowlist | why V1.6 needs it | blocked by |
|---|---|---|---|---|---|
| `help.getConfig` | `0xc4f9186b` | true | **yes** | The health probe a dashboard shows for an account. | — |
| `help.getNearestDc` | `0x1fb33026` | true | **yes** | Which DC an account is on — the same fact the presence feed shows. | — |
| `users.getUsers` | `0x0d91a548` | true | **yes** | Name a peer for a handler's decision. Circular exactly as in V1.1. | — |
| `users.getFullUser` | `0xb60f5918` | true | **yes** | Bot-vs-human discrimination: `user.bot` lives on `User`, full profile on `UserFull`. | — |
| `account.getPassword` | `0x548a30f5` | true | **yes** | Whether the account is protected — a dashboard fact. | — |

**Ordered request list — V1.6.** 1. `help.getNearestDc`. 2. `help.getConfig`. 3. `users.getUsers`
(circular, if the handler needs a peer name). 4. `users.getFullUser` (bot flag). Everything else
V1.6 does — AI answering, observers, Redis hot reload — is local and never calls Telegram;
`AccountLifecycleObserver` is documented as never throwing and degrading without Redis
(`src/Teleframe/Mirror/…TfAccount` / the V1.6 row of the readiness table).

**V1.6 works read-only for health and topology (steps 1, 2, 4), and is blocked only for peer-name
hydration (step 3), by V1.1's circularity.**

## 3. Escape-hatch analysis — can each capability work read-only without widening the allowlist?

Each is proved or refuted below. "Read-only" means: no change to `LiveEndpointPolicy`.

**V1.1 — STRUCTURALLY IMPOSSIBLE. Proved, not asserted.**
`users.getUsers` takes `id: Vector<InputUser>` and `users.getFullUser` takes `id: InputUser` — both
read from the catalog's `params`. `InputUser`'s four constructors are at
`src/Schema/schema/sources/api_full.tl:20-23`:

```
inputUserEmpty#b98886cf = InputUser;
inputUserSelf#f7c1b13f = InputUser;
inputUser#f21158c6 user_id:long access_hash:long = InputUser;
inputUserFromMessage#1fa448e2 peer:InputPeer msg_id:int user_id:long = InputUser;
```

Line 22 is decisive: `access_hash` is an **input** to the constructor. So both allowlisted
`users.*` methods can only re-verify a hash the caller already holds. There is one fourth form,
`inputUserFromMessage` (line 23), which takes a `peer:InputPeer` + `msg_id` *instead of* a hash —
it does not escape the circle, because the peer it needs comes from the same `access_hash`-bearing
sources. Every source of a fresh `access_hash` — `messages.getDialogs`, `messages.getHistory`,
`updates.getState`, `contacts.resolveUsername` — is outside the allowlist. **A hash cannot be
obtained without an allowed method, and an allowed method cannot be used without one.** Circular,
therefore structurally unprovable. This is confirmed independently at
`docs/schema/mtproto-data-shape-map.md:142-155` and
`.brain/evidence/v1-live-reachability.md`.

**V1.1.1 — what read-only DOES buy, precisely.** Three of the six allowlisted entries take `[]`:
`help.getConfig` (`params=[]`), `help.getNearestDc` (`params=[]`), `account.getPassword`
(`params=[]`). So a read-only lane can prove **session liveness, DC topology, and two-step state**
against real Telegram with no hash at all. It cannot resolve a single peer. That is the honest
boundary of V1.1 under the current policy, and it is a real, if narrow, result.

**V1.2 — STRUCTURALLY IMPOSSIBLE (Telegram storage half).** A backup that reads and writes
chunks requires `messages.search`, `messages.getMessages` and `upload.getFile` in sequence, and
all three are NOT-ALLOWLISTED, with `upload.getFile` verb-denied twice over
(`DESTRUCTIVE_VERBS:63`). There is no read-only ordering of three denied methods. **Refuted** as
to the `s3` driver: it needs no Telegram method and is unaffected. (`ftp`/`rsync` are named in
the verbatim but were never implemented and are not drivers — do not read them into this row.)

**V1.3 — POSSIBLE for the local half, IMPOSSIBLE for the Telegram half.** No allowlist change is
needed to dump a Laravel DB and chunk it; the dumper ships and `ChunkPlanner` sizes the parts
(`src/Teleframe/Store/ChunkPlanner.php:47`, `:55`). The remaining blockers are not allowlist
blockers: the default dumper
`'null'`, and V1.2's Telegram chain. **This is the capability closest to done.**

**V1.4 — POSSIBLE, and already done.** No allowlist entry is required, because the requirement is
machine-enforced offline (36-dial / 34-serialise, 13 tests / 89 assertions). The 36
`callable=false` methods are correctly so and are **NOT-CALLABLE by design** — they are
envelopes, MTProto service requests, or catalog-only declarations, and the catalog's own
`wire.reason` says which.

**V1.5 — IMPOSSIBLE, and widening the allowlist would not fix it.** `auth.sendCode` +
`auth.signIn` are both verb-denied, and step 1 additionally needs a human to type the code that
`auth.SentCode` returns. **NEEDS-HUMAN survives any policy change** — this is the distinction the
five categories exist to protect. A second, independent blocker: Passport is a *bot* feature and
`PassportConfig::botId()` returns `config['bot_id'] ?? 0`, so with `telegram_accounts` empty the
flow has no `telegram.org` URL to send the user to.

**V1.6 — POSSIBLE for health/topology/bot-flag; IMPOSSIBLE for peer-name hydration.** Steps 1, 2
and 4 of its list are allowlisted and hash-free. Step 3 inherits V1.1's circularity exactly.
Everything non-Telegram in V1.6 (observers, AI answering, Redis hot reload) is local by design.

### Summary of the escape-hatch question

| capability | read-only under current allowlist? | the one-line reason |
|---|---|---|
| V1.1 | **NO — structurally impossible** | `access_hash` is an *input* (`api_full.tl:22`); every source of one is denied. |
| V1.2 | **NO** (Telegram half) | its three-method chain is denied, one of them verb-denied twice. |
| V1.3 | **PARTLY — local half yes** | no Telegram method needed to dump; blockers are the `'null'` default and V1.2. |
| V1.4 | **YES — already discharged** | requirement is enforced offline; needs no live call. |
| V1.5 | **NO — and widening would not help** | NEEDS-HUMAN survives the policy; plus no bot id is configured. |
| V1.6 | **PARTLY** | health/DC/bot-flag yes; peer-name hydration inherits V1.1. |

## 4. Ordered request lists, collected

For an ingestor that "requests any missing data while you ingest", these are the sequences. Each
step is annotated with whether it can run today.

**A peer `user` row is missing (blocks V1.1, V1.6):**

| # | call | runs today? |
|---|---|---|
| 1 | `help.getNearestDc` | **yes** |
| 2 | `help.getConfig` | **yes** |
| 3 | `users.getUsers([inputUser(uid, hash)])` | **no — `hash` unobtainable** |
| 4 | `messages.getDialogs(...)` (the hash source) | no — denied |
| 5 | `users.getFullUser(inputUser(uid, hash))` | no — depends on 3 |

**A `chat` row is missing (a `Chat`/`ChatFull` reference; shape map §4):**

| # | call | runs today? |
|---|---|---|
| 1 | `chats.getChats([id])` | **never — NOT-CATALOGUED, no such method** |
| 1' | `messages.getChats([id])` `0x49e9528f` | no — denied (and note it takes a **bare** `Vector<long>`, so unlike the users chain it needs **no `access_hash`**) |
| 2 | `messages.getFullChat` `0xaeb00b34` | no — denied |
| 3 | `channels.getFullChannel` `0x08736a09` | no — denied |
| 4 | `channels.getParticipants` `0x77ced9d0` | no — denied |

**This is the one hydration chain that is blocked by POLICY ONLY, not by circularity.** Step 1'
takes bare ids. If `messages.getChats` were allowlisted, a chat row could be hydrated with no hash
at all — the only such chain in the whole matrix.

**A message reference is dangling (`reply_to_msg_id` / forward source; shape map §5.2 — the
ordering failure the owner named):**

| # | call | runs today? |
|---|---|---|
| 1 | `messages.getMessages([reply_to_msg_id])` `0x63c66506` | no — denied |
| 2 | if absent from the response, `messages.getHistory(peer, min_id=max_id, limit=1)` `0x4423e6c5` | no — denied |
| 3 | else mark the reference unresolved and retry when the range is covered | **yes** — this is the only step in any list that needs no RPC |

**A media/document is referenced (shape map §4):**

| # | call | runs today? |
|---|---|---|
| 1 | `upload.getFile(location, offset)` `0xbe5335be` in a part loop (`TelegramVault.php:935`) | no — denied twice (not listed + verb `upload`) |

**A backup chunk is read (V1.2 / V1.3-telegram):**

| # | call | runs today? |
|---|---|---|
| 1 | `messages.search(q=<name hash>)` `0x29ee847a` | no — denied |
| 2 | `messages.getMessages([id])` → `Document` | no — denied |
| 3 | `upload.getFile(...)` until a short part | no — denied |

## 5. The 201 constructors — why this matters for ingest, stated once

`docs/schema/mtproto-data-shape-map.md:23` records **201** constructors carrying at least one
reference. Each such constructor, when it arrives in an update, can name a peer, a chat, a
message, a photo or a document that the mirror does not yet hold. This matrix is the lookup for
"which fetch do I issue for that reference" — §4 above is that lookup, in ingest order. Of the
chains in §4, exactly one (the chat chain) is blocked by policy only; the rest are blocked by
circularity (peers), by policy (messages, media, chunks), or by the absence of a method (a bare
`chat` under its catalogued name).

## 6. Closing summary — grouped by who can act

### Package source change (only an agent can do these; no configuration or deployment helps)

1. **Add `messages.getChats` (`0x49e9528f`) and `messages.getFullChat` (`0xaeb00b34`) to
   `LIVE_SAFE`.** Highest value per line in this document: the chat hydration chain is the only
   one that needs **no `access_hash`**, so allowlisting it makes a whole reference class
   hydratable read-only. Every other chain is blocked by more than the allowlist.
2. **Add `messages.getMessages` (`0x63c66506`)** — resolves `reply_to`/forward targets, which is
   the owner's named ordering failure. Its `errors` list does not include a write verb.
3. **Fix or remove the `auth.getPassword` allowlist entry**
   (`src/Teleframe/Warm/LiveEndpointPolicy.php:88-90`). It names a method that is not in the
   823-method catalog, so it can never do what its comment claims. Either the catalog is missing a
   method or the entry is stale; either way an allowlist that cannot fire is a gate that silently
   does not gate. *(Not fixed here — this lane may not alter the allowlist.)*
4. **Decide the `chats.*` naming.** The shape map's §4 row `Chat → chats.getChats (ABSENT)` is
   half true: `chats.getChats` is genuinely absent, but `messages.getChats` exists, is callable,
   and does the job. Both spellings should appear in the shape map so the next agent does not
   re-derive it. *(Not fixed here — that file is DONE and owned by another lane.)*
5. **Wire the `setPassportDataErrors` POST.** `PassportErrorReporter` builds the
   array and dispatches the MTProto twin; the HTTP call itself is still the
   host's to make.

### Configuration only (a host operator changes a value; no code)

6. **Set `teleframe.backup.driver` to `s3`** — this makes V1.3's Telegram-independent
   half usable immediately and dodges V1.2's impossible chain entirely. `s3` is the only
   implemented Telegram-independent driver; the accepted values are `memory`, `telegram` and
   `s3`, and any other value throws `RuntimeException` at bind time
   (`TeleframeServiceProvider.php:468`). **`ftp` and `rsync` were never implemented** — an
   earlier revision of this document prescribed them, which sent operators to a config value
   the provider rejects. They are named in the original verbatim but ship in no code path;
   do not re-advertise them without implementing them first.
7. **Set a non-`'null'` DB dumper** — the shipped default cannot dump a production database
   (`.brain/verbatim-goals.md`, V1.3 row).
8. **Set `teleframe.passport.bot_id`** to a real bot id — without it `botId()` returns 0 and
   `telegram_url` is `null`, so the Passport flow has nowhere to send the user.

### CI deployment (an environment needs a binary or a service; no code changes)

9. **A RoadRunner (or Temporal activity-execution) binary.** A live Temporal server exists at
   `127.0.0.1:7233` and dispatches this package's workflow types
   (`.brain/evidence/v1-live-reachability.md:149-179`), but without the worker binary **activities
   cannot execute**. This blocks no capability's *method* access — it is an environment gap, and
   it is the reason some V1.2/V1.3 backing work cannot be proven even where the methods would be
   allowed.

### The operator (only a person can do these)

10. **Type the interactive code for V1.5** — `auth.sendCode` returns a `phone_code` that only a
    human at a keyboard can read, and `auth.checkPassword` needs a 2FA password. **NEEDS-HUMAN
    survives any allowlist change.**
11. **Decide the allowlist-widening trade.** `LiveEndpointPolicy:20-22` states the asymmetry: "a
    wrongly-denied method costs a skipped test, a wrongly-allowed method can cost the account."
    Every item in the package-source-change group is a deliberate act under that rule, and the
    owner — not an agent — owns the risk. Nothing in this document changes the allowlist.
12. **Provide a Telegram bot for Passport** if V1.5 is to be completed at all, and the account for
    the live run the owner asked for verbatim: *"you should have used one of bots in my dedicated
    telegram account for this work to make sure passport is working since i am going to use this
    passport in a lot of my apps and it must work flawlessly."*

## 7. What this document does not claim

- **No method here was called.** Every `callable` value is the catalog's own flag; every
  allowlist judgement is `refusalReason()`'s logic read from source. Nothing was executed against
  Telegram, no warm lane ran, no database was contacted, no credential was read.
- **"Structurally impossible" is a statement about the current policy, not about MTProto.** All
  of V1.1/V1.2/V1.5's methods are callable on the wire. The impossibility is the *combination* of
  a deny-by-default allowlist and a hash-circular input contract.
- **The four conclusions not verified here, and why.** (a) Whether the 36 `callable=false` methods
  include anything V1.4 actually needs — I read the catalog's `wire.reason` classes, not all 36
  against the 36-dial test. (b) Whether `messages.getChats` and `messages.getFullChat` are
  *genuinely* side-effect-free reads — I verified they are in the catalog and callable, and that
  neither verb matches `DESTRUCTIVE_VERBS`, but the substantive safety case is the same judgement
  the policy demands of every entry and is the owner's to make. (c) The precise V1.3 dumper
  configuration key — I report the `'null'` default as recorded, not a key I read.
  (d) The exact `PassportConfig` file:line for `botId()`; I read the behaviour from the recorded
  live response, not from source, so it is cited to
  `.brain/evidence/passport-hot-test-2026-10-01.md` rather than to a line I confirmed.