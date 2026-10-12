# Stage 1 ingest — the record behind the map

This file is the **evidence record** for stage 1 of the owner's method. The map itself
(`docs/schema/mtproto-data-shape-map.md`) is what an agent follows; this file is what a future
agent re-runs to notice that the map has decayed. A map nobody re-verifies rots silently, so
every number the map depends on is recorded here with the exact command that produced it.

**Rules for this file, binding on whoever edits it next:**

- Record only output you personally observed. Never carry a number forward because it was quoted
  to you.
- A fact that no longer reproduces is recorded as **DRIFTED** with what you actually got. A
  drifted fact recorded honestly is worth more than a confident wrong one — because a reader can
  act on it, and a wrong one silently poisons every map derived from it.
- If a sentence here cannot be traced to a command you ran, delete it.

Verification date for the values below: measured in this working tree, commands as written, each
bounded with `timeout -k 5 <secs>`. No Telegram contact, no database, no credentials read.

---

## Fact 1 — DRIFTED: the "201 constructors carrying a reference" headline does not reproduce

**CLAIM (as given to the record):** 201 constructors in `src/Schema/schema/sources/api_full.tl`
carry at least one reference field. Recipe: split on `;` (never on `=`, a field may contain one);
extract fields with `([a-z_0-9]+):(flags\d*\.\d+\?)?([A-Za-z_][A-Za-z_0-9<>.]*)`; keep
constructors referencing an entity type.

**VERDICT: DRIFTED.** The sub-claim about `message` reproduces exactly; the headline number does
not, and no interpretation of the stated recipe yields 201.

### 1a. The `message` sub-claim — REPRODUCES EXACTLY

**COMMAND:**
```
timeout -k 5 20 python3 -c "
import importlib.util
spec=importlib.util.spec_from_file_location('m','/tmp/codeg-acp/211710-9bc33f50/opencode/tl-refs.py')
m=importlib.util.module_from_spec(spec); spec.loader.exec_module(m)
ct=dict(m.parse('src/Schema/schema/sources/api_full.tl'))
f=ct['message']
print('message fields:',len(f))
refs=[(a,c) for a,b,c in f if c in {'Peer','MessageFwdHeader','MessageReplyHeader','MessageMedia'}]
print('message refs  :',len(refs),refs)"
```

**OBSERVED:**
```
message fields: 49
message refs  : 7 [('from_id', 'Peer'), ('peer_id', 'Peer'), ('saved_peer_id', 'Peer'), ('fwd_from', 'MessageFwdHeader'), ('guestchat_via_from', 'Peer'), ('reply_to', 'MessageReplyHeader'), ('media', 'MessageMedia')]
```

**FILE:LINE:** `src/Schema/schema/sources/api_full.tl:121` — `message#7600b9d3`. Confirmed by
reading the line directly:
```
timeout -k 5 20 grep -n "^message#7600b9d3" src/Schema/schema/sources/api_full.tl
```
which returns the definition on line 121, carrying `id:int from_id:flags.8?Peer … peer_id:Peer
saved_peer_id:flags.28?Peer fwd_from:flags.2?MessageFwdHeader … guestchat_via_from:flags2.19?Peer
reply_to:flags.3?MessageReplyHeader … media:flags.9?MessageMedia …`.

So: **49 fields, 7 references, at :121 — confirmed.** This is the single most load-bearing number
in the map, because the seven referenced types are exactly what forces the 35 companion tables of
Fact 3.

### 1b. The headline count — 201 NOT REPRODUCED

The recipe does not pin down what "an entity type" means, so the count depends entirely on that
undefined choice. Every reading was measured on the same parse of `api_full.tl`:

**COMMAND (one script, all readings):**
```
timeout -k 5 60 python3 - <<'PY'
import importlib.util
spec=importlib.util.spec_from_file_location("m","/tmp/codeg-acp/211710-9bc33f50/opencode/tl-refs.py")
m=importlib.util.module_from_spec(spec); spec.loader.exec_module(m)
ct=m.parse('src/Schema/schema/sources/api_full.tl')
PRIM={'int','long','int128','int256','float','double','bool','true','string','bytes','Date','InputFile'}
def c(p): return sum(1 for n,f in ct if any(p(ft) for _,_,ft in f))
print('ctors parsed                       :',len(ct))
print('non-prim, Vector<...> excluded     :',c(lambda t:t not in PRIM and not t.startswith('Vector')))
print('non-prim, any generic allowed      :',c(lambda t:t not in PRIM))
print('flags-conditional non-prim         :',sum(1 for n,f in ct if any(fl and ft not in PRIM for _,fl,ft in f)))
E={'Peer','User','Chat','Channel','Message','MessageMedia','Dialog','Photo','Document','ChatParticipants','ChatFull','ChannelFull','UserFull','MessageEntity','MessageFwdHeader','MessageReplyHeader','MessageAction','SponsoredMessage','StoryItem','WallPaper','InputPeer'}
print('entity-type allowlist +InputPeer   :',c(lambda t:t in E))
PY
```

**OBSERVED:**
```
ctors parsed                       : 1303
non-prim, Vector<...> excluded     : 553
non-prim, any generic allowed      : 655
flags-conditional non-prim         : 209
entity-type allowlist +InputPeer   : 191
```

Closest measured value to the claimed 201 is **209** (references that are flag-conditional), then
**191** (a hand-listed entity-type set, which is arbitrary because the set is invented by the
reader). No reading produces 201.

**FILE:LINE:** `src/Schema/schema/sources/api_full.tl` (1303 constructors parsed from this file).

**What a future agent should do with this:** do not cite 201. If the map's scale line needs a
number, cite the measured 209 or 553 **together with the definition used**, or replace the
headline with the only number that is definition-independent — the seven references on `message`
(1a), which is what the rest of the map actually builds on. A count with an unstated definition
is not a fact, it is a rumour.

The parser used for all of 1a/1b lives at
`/tmp/codeg-acp/211710-9bc33f50/opencode/tl-refs.py` (scratch, not committed). Its contract: strip
`//` comments, split on `;`, drop `---`/`functions`/`vector` prefixes, match
`^([A-Za-z_][A-Za-z_0-9]*)(?:#[0-9a-f]+)?\s+(.*)$`, then apply the stated field regex.

**Sanity note on the same recipe against sibling files** (all four `.tl` sources, same script):
```
timeout -k 5 60 python3 /tmp/codeg-acp/211710-9bc33f50/opencode/tl-refs.py src/Schema/schema/sources/api.tl        # 629
timeout -k 5 60 python3 /tmp/codeg-acp/211710-9bc33f50/opencode/tl-refs.py src/Schema/schema/sources/api_full.tl   # 621
timeout -k 5 60 python3 /tmp/codeg-acp/211710-9bc33f50/opencode/tl-refs.py src/Schema/schema/sources/mtproto.tl      # 5
timeout -k 5 60 python3 /tmp/codeg-acp/211710-9bc33f50/opencode/tl-refs.py src/Schema/schema/sources/mtproto_full.tl # 5
```
(These are the `non-prim incl Vector<non-prim>` reading, i.e. the "655" definition applied to each
file — which yields 621 for `api_full.tl` because the entity-type/generic bookkeeping differs.
Cite the definition whenever any of these is quoted.)

---

## Fact 2 — REPRODUCES EXACTLY: 823 methods, catalog layer 229, wire mirror 227

**COMMAND:**
```
timeout -k 5 30 python3 -c "import json;d=json.load(open('src/Schema/schema/methods-mtproto.json'));print(len(d['methods']),d['layer'])"
```

**OBSERVED:**
```
823 229
```

**FILE:LINE:** `src/Schema/schema/methods-mtproto.json` (`methods` and `layer` top-level keys).

The wire mirror is a separate, independently measured number — do not infer it from the catalog:
```
timeout -k 5 20 grep -n "const LAYER" src/Core/MTProto/Connection/EncryptedConnection.php
timeout -k 5 20 grep -n "telegram-layer" composer.json
```

**OBSERVED:**
```
38:    public const LAYER = 227;
140:        "telegram-layer": 229,
```

So the catalog declares 229 (`composer.json:140`, and the JSON's own `layer` key) while the wire
speaks 227 (`src/Core/MTProto/Connection/EncryptedConnection.php:38`). **This gap is intentional
and must never be "fixed"** — AGENTS.md says so, and both halves are now measured rather than
assumed.

---

## Fact 3 — DRIFTED (minor, and it matters): 36 `Schema::create('tf_messages…')` calls, but only 35 are companion tables

**CLAIM (as given to the record):** 36 tables named `tf_messages_*` in
`src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php`.

**VERDICT: DRIFTED.** The number 36 is right but it counts the **parent table** `tf_messages`
itself. The companion count — the number the map actually depends on — is **35**.

**COMMAND:**
```
timeout -k 5 20 grep -n "Schema::create" src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php
```

**OBSERVED (36 lines; note line 88 is the parent, not a companion):**
```
88:        Schema::create('tf_messages', function (Blueprint $table) {
126:        Schema::create('tf_messages_from_id', function (Blueprint $table) {
134:        Schema::create('tf_messages_from_boosts_applied', function (Blueprint $table) {
141:        Schema::create('tf_messages_from_rank', function (Blueprint $table) {
148:        Schema::create('tf_messages_saved_peer_id', function (Blueprint $table) {
157:        Schema::create('tf_messages_fwd_from', function (Blueprint $table) {
179:        Schema::create('tf_messages_via_bot_id', function (Blueprint $table) {
186:        Schema::create('tf_messages_via_business_bot_id', function (Blueprint $table) {
193:        Schema::create('tf_messages_guestchat_via_from', function (Blueprint $table) {
205:        Schema::create('tf_messages_reply_to', function (Blueprint $table) {
227:        Schema::create('tf_messages_views', function (Blueprint $table) {
234:        Schema::create('tf_messages_forwards', function (Blueprint $table) {
243:        Schema::create('tf_messages_replies', function (Blueprint $table) {
255:        Schema::create('tf_messages_edit_date', function (Blueprint $table) {
262:        Schema::create('tf_messages_post_author', function (Blueprint $table) {
269:        Schema::create('tf_messages_grouped_id', function (Blueprint $table) {
276:        Schema::create('tf_messages_ttl_period', function (Blueprint $table) {
283:        Schema::create('tf_messages_quick_reply_shortcut_id', function (Blueprint $table) {
290:        Schema::create('tf_messages_effect', function (Blueprint $table) {
297:        Schema::create('tf_messages_report_delivery_until_date', function (Blueprint $table) {
304:        Schema::create('tf_messages_paid_message_stars', function (Blueprint $table) {
311:        Schema::create('tf_messages_schedule_repeat_period', function (Blueprint $table) {
318:        Schema::create('tf_messages_summary_from_language', function (Blueprint $table) {
326:        Schema::create('tf_messages_restriction_reason', function (Blueprint $table) {
339:        Schema::create('tf_messages_reactions', function (Blueprint $table) {
349:        Schema::create('tf_messages_factcheck', function (Blueprint $table) {
361:        Schema::create('tf_messages_suggested_post', function (Blueprint $table) {
374:        Schema::create('tf_messages_reply_markup', function (Blueprint $table) {
386:        Schema::create('tf_messages_reply_markup_rows', function (Blueprint $table) {
394:        Schema::create('tf_messages_reply_markup_rows_buttons', function (Blueprint $table) {
418:        Schema::create('tf_messages_service', function (Blueprint $table) {
440:        Schema::create('tf_messages_service_from_id', function (Blueprint $table) {
448:        Schema::create('tf_messages_service_saved_peer_id', function (Blueprint $table) {
456:        Schema::create('tf_messages_service_reply_to', function (Blueprint $table) {
478:        Schema::create('tf_messages_service_reactions', function (Blueprint $table) {
487:        Schema::create('tf_messages_service_ttl_period', function (Blueprint $table) {
```

**FILE:LINE:** `src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php`, lines
88/126/134/…/487.

**Corrected counts, measured:**
```
timeout -k 5 20 python3 -c "
import re
src=open('src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php').read().splitlines()
c=[(i+1,re.search(r\"Schema::create\('([^']+)'\",l).group(1)) for i,l in enumerate(src) if \"Schema::create('tf_messages\" in l]
print('tf_messages* create calls :',len(c))
print('companions (no parent)    :',len([x for x in c if x[1]!='tf_messages']))
print('service family            :',[x[1] for x in c if x[1].startswith('tf_messages_service')])"
```

**OBSERVED:**
```
tf_messages* create calls : 36
companions (no parent)    : 35
service family            : ['tf_messages_service', 'tf_messages_service_from_id', 'tf_messages_service_saved_peer_id', 'tf_messages_service_reply_to', 'tf_messages_service_reactions', 'tf_messages_service_ttl_period']
```

So the map's "36 companion tables" is really **35 companions + 1 parent**. If a future stage asks
"does every companion table have a model?", the denominator is **35**, not 36 — using 36
silently over-counts by one and will look like a missing model.

### 3a. The four reference companions, with exact extents

All four exist and each carries the `(account_id, id)` 1:1 key. Line ranges measured by locating
the `Schema::create` and scanning to its closing `});`:

| wire field | table | create line | block closes | primary |
|---|---|---|---|---|
| `from_id:Peer` | `tf_messages_from_id` | 126 | 132 | `['account_id','id']` |
| `saved_peer_id:Peer` | `tf_messages_saved_peer_id` | 148 | 154 | `['account_id','id']` |
| `fwd_from:MessageFwdHeader` | `tf_messages_fwd_from` | 157 | 177 | `['account_id','id']` |
| `reply_to:MessageReplyHeader` | `tf_messages_reply_to` | 205 | 225 | `['account_id','id']` |
| `guestchat_via_from:Peer` | `tf_messages_guestchat_via_from` | 193 | 199 | `['account_id','id']` |

**OBSERVED of the two the map quotes by line:**
```
timeout -k 5 20 sed -n '156,157p' src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php
```
```
        // fwd_from — single-ctor MessageFwdHeader flattened 1:1.
        Schema::create('tf_messages_fwd_from', function (Blueprint $table) {
```
The "flattened 1:1" comment is at **:156**, the `Schema::create` at **:157** — so the map's
":156-163" points at the comment plus the first few columns, not at the whole table; the table
runs to **:177**.

`reply_to`'s companion carries the dangling-reference column the map's failure analysis rests on:
```
timeout -k 5 20 sed -n '205,225p' src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php
```
```
        Schema::create('tf_messages_reply_to', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->text('constructor');
            $table->boolean('reply_to_scheduled')->default(false);
            $table->boolean('forum_topic')->default(false);
            $table->boolean('quote')->default(false);
            $table->boolean('reply_to_ephemeral')->default(false);
            $table->integer('reply_to_msg_id')->default(0);
            $table->tinyInteger('reply_to_peer_id_type')->default(0);
            $table->bigInteger('reply_to_peer_id_id')->default(0);
            $table->integer('reply_to_top_id')->default(0);
            $table->text('quote_text')->default(DB::raw("('')"));
            $table->integer('quote_offset')->default(0);
            $table->integer('todo_item_id')->default(0);
            $table->text('poll_option')->default(DB::raw("('')"));
            $table->tinyInteger('peer_type')->default(0);
            $table->bigInteger('peer_id')->default(0);
            $table->integer('story_id')->default(0);
            $table->primary(['account_id', 'id']);
        });
```
`reply_to_msg_id` at **:213** is the column a backfill can point at a message it has not crawled.
The migration comment above it states the flat-decomposer limitation explicitly (:201-204):
"Nested reply_from / reply_media / quote_entities cannot nest under the flat decomposer write path
(child columns are flat by construction) → deferred." So the reply companion is itself
*incomplete* by design — worth carrying into the models stage.

Also observed: `Schema::dropIfExists('tf_messages_reply_to');` at **:74** (in `down()`).

---

## Fact 4 — REPRODUCES EXACTLY (with a range caveat): `tf_dialogs` keys on `(account_id, peer_type, peer_id)`

**COMMAND:**
```
timeout -k 5 20 sed -n '51,56p;69,73p' src/Laravel/Migrations/2026_09_14_200004_create_tf_dialogs_tables.php
```

**OBSERVED:**
```
        Schema::create('tf_dialogs', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->tinyInteger('peer_type')->default(0);
            $table->bigInteger('peer_id')->default(0);
            $table->text('constructor');
            $table->integer('top_message')->default(0);
            $table->boolean('view_forum_as_messages')->default(false);

            $table->primary(['account_id', 'peer_type', 'peer_id']);
        });
    }
```

**FILE:LINE:** `src/Laravel/Migrations/2026_09_14_200004_create_tf_dialogs_tables.php` —
`Schema::create` at **:51**, key columns at **:52-54**, `primary()` at **:71**, block closes at
**:72**.

**Caveat on the quoted range ":51-89":** the `tf_dialogs` table itself is **:51-72**. The range
51-89 runs past the closing brace at :73 into the first child table. The key claim is exact; the
line range is loose. Cite `:51` and `:71`.

---

## Fact 5 — REPRODUCES EXACTLY: all seven `tf_channel_participants*` tables exist

**COMMAND:**
```
timeout -k 5 20 grep -rn "Schema::create('tf_channel_participants" src/Laravel/Migrations/
```

**OBSERVED:**
```
src/Laravel/Migrations/2026_09_14_200031_create_tf_channel_participants_tables.php:72:        Schema::create('tf_channel_participants', function (Blueprint $table) {
src/Laravel/Migrations/2026_09_14_200031_create_tf_channel_participants_tables.php:89:        Schema::create('tf_channel_participants_peer', function (Blueprint $table) {
src/Laravel/Migrations/2026_09_14_200031_create_tf_channel_participants_tables.php:99:        Schema::create('tf_channel_participants_inviter', function (Blueprint $table) {
src/Laravel/Migrations/2026_09_14_200031_create_tf_channel_participants_tables.php:108:        Schema::create('tf_channel_participants_promoted_by', function (Blueprint $table) {
src/Laravel/Migrations/2026_09_14_200031_create_tf_channel_participants_tables.php:117:        Schema::create('tf_channel_participants_kicked_by', function (Blueprint $table) {
src/Laravel/Migrations/2026_09_14_200031_create_tf_channel_participants_tables.php:126:        Schema::create('tf_channel_participants_subscription_until_date', function (Blueprint $table) {
src/Laravel/Migrations/2026_09_14_200031_create_tf_channel_participants_tables.php:135:        Schema::create('tf_channel_participants_rank', function (Blueprint $table) {
```

**FILE:LINE:** all seven in
`src/Laravel/Migrations/2026_09_14_200031_create_tf_channel_participants_tables.php` —
base `:72`, `_peer` `:89`, `_inviter` `:99`, `_promoted_by` `:108`, `_kicked_by` `:117`,
`_subscription_until_date` `:126`, `_rank` `:135`.

7 of 7 claimed tables observed, none missing. On-disk order differs from the order usually listed
(`_promoted_by` precedes `_kicked_by`; `_rank` is last).

---

## Fact 6 — REPRODUCES EXACTLY: the allowlist is exactly six methods

**COMMAND:**
```
timeout -k 5 20 grep -n "LIVE_SAFE\|'users\.\|'help\.\|'auth\.\|'account\." src/Teleframe/Warm/LiveEndpointPolicy.php
```

**OBSERVED:**
```
18: * LIVE_SAFE below. Everything else — the 132 destructive ones, and the ~690
75:    public const LIVE_SAFE = [
76:        'users.getUsers' =>
79:        'users.getFullUser' =>
82:        'help.getConfig' =>
85:        'help.getNearestDc' =>
88:        'auth.getPassword' =>
92:        'account.getPassword' =>
102:            return array_keys(self::LIVE_SAFE);
107:            return isset(self::LIVE_SAFE[$method]);
139:            . 'LiveEndpointPolicy::LIVE_SAFE with a reason ONLY if it is genuinely proven safe.',
```

**FILE:LINE:** `src/Teleframe/Warm/LiveEndpointPolicy.php` — the array literal
`public const LIVE_SAFE` opens at **:75**; entries at **:76** `users.getUsers`, **:79**
`users.getFullUser`, **:82** `help.getConfig`, **:85** `help.getNearestDc`, **:88**
`auth.getPassword`, **:92** `account.getPassword`. **Count observed: 6.**

It is a **const array keyed method-name => reason string**, not a bare list; `liveSafeMethods()`
returns `array_keys(self::LIVE_SAFE)` at :102 and `isLiveSafe()` is an `isset` at :107.

Note the file's own doc comment at :18 describes "the 132 destructive ones, and the ~690" remaining
— which is why the *catalog* is 823 (Fact 2) while the allowlist is 6. Do not confuse the two
numbers.

---

## Fact 7 — REPRODUCES EXACTLY, and the claim is stronger than stated: there is no `chats` namespace at all

**COMMAND:**
```
timeout -k 5 30 python3 -c "import json;d=json.load(open('src/Schema/schema/methods-mtproto.json'));ms=d['methods'];print('total',len(ms));print('layer',d['layer']);print('chats.getChats present:', 'chats.getChats' in ms);print('chats.* present:', sorted(n for n in ms if n.startswith('chats.')))"
```

**OBSERVED:**
```
total 823
layer 229
chats.getChats present: False
chats.* present: []
```

**FILE:LINE:** `src/Schema/schema/methods-mtproto.json` — `methods` is a **dict keyed by method
name** (823 keys), so membership is tested against the keys.

`chats.getChats` is absent, and **so is the entire `chats` namespace** — the filtered list is
empty, not merely missing one member. There is therefore no method by that name to hydrate a bare
chat id, and no sibling to substitute. The near-miss names that do exist are `messages.getChats`
(`:15816`) and `stories.getChatsToSend` (`:31604`) — different methods with different signatures,
not aliases.

---

## How to re-verify the whole map

Run these **in order** from the repo root. Each is bounded; none touches Telegram, a database, or
`.env`. Compare every line against the section above — a difference means the map has drifted and
this file needs updating, not the map being silently trusted.

```bash
# 0. Sanity: exactly one new untracked file should be this document, and the tree otherwise clean.
timeout -k 5 10 git status --short

# 1. Fact 1a — the load-bearing fact: 49 fields / 7 references on `message`, at :121.
timeout -k 5 20 grep -n "^message#7600b9d3" src/Schema/schema/sources/api_full.tl
timeout -k 5 20 python3 /tmp/codeg-acp/211710-9bc33f50/opencode/tl-refs.py src/Schema/schema/sources/api_full.tl

# 1b. Fact 1b — the DRIFTED headline. Expect the reading to be quoted WITH its definition.
timeout -k 5 60 python3 - <<'PY'
import importlib.util
spec=importlib.util.spec_from_file_location("m","/tmp/codeg-acp/211710-9bc33f50/opencode/tl-refs.py")
m=importlib.util.module_from_spec(spec); spec.loader.exec_module(m)
ct=m.parse('src/Schema/schema/sources/api_full.tl')
PRIM={'int','long','int128','int256','float','double','bool','true','string','bytes','Date','InputFile'}
def c(p): return sum(1 for n,f in ct if any(p(ft) for _,_,ft in f))
print('ctors parsed                    :',len(ct))
print('non-prim, Vector excluded       :',c(lambda t:t not in PRIM and not t.startswith('Vector')))
print('non-prim, any generic allowed   :',c(lambda t:t not in PRIM))
print('flags-conditional non-prim      :',sum(1 for n,f in ct if any(fl and ft not in PRIM for _,fl,ft in f)))
PY

# 2. Fact 2 — catalog scale and the intentional 229/227 gap.
timeout -k 5 30 python3 -c "import json;d=json.load(open('src/Schema/schema/methods-mtproto.json'));print(len(d['methods']),d['layer'])"
timeout -k 5 20 grep -n "const LAYER" src/Core/MTProto/Connection/EncryptedConnection.php
timeout -k 5 20 grep -n "telegram-layer" composer.json

# 3. Fact 3 — companion table census. 36 create calls, 35 companions. Line numbers move as tables are added.
timeout -k 5 20 grep -c "Schema::create" src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php
timeout -k 5 20 grep -n "Schema::create" src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php
timeout -k 5 20 sed -n '126,132p;148,154p;156,157p;193,199p;205,225p' src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php

# 4. Fact 4 — tf_dialogs key. Cite :51 (create) and :71 (primary), NOT the range 51-89.
timeout -k 5 20 sed -n '51,56p;69,73p' src/Laravel/Migrations/2026_09_14_200004_create_tf_dialogs_tables.php

# 5. Fact 5 — the seven participants tables, all in one migration.
timeout -k 5 20 grep -rn "Schema::create('tf_channel_participants" src/Laravel/Migrations/

# 6. Fact 6 — the six live-safe methods. This is the gate that decides what is provable without a real account.
timeout -k 5 20 grep -n "LIVE_SAFE\|'users\.\|'help\.\|'auth\.\|'account\." src/Teleframe/Warm/LiveEndpointPolicy.php

# 7. Fact 7 — no chats namespace at all.
timeout -k 5 30 python3 -c "import json;d=json.load(open('src/Schema/schema/methods-mtproto.json'));ms=d['methods'];print('total',len(ms));print('layer',d['layer']);print('chats.getChats present:', 'chats.getChats' in ms);print('chats.* present:', sorted(n for n in ms if n.startswith('chats.')))"
```

**Note:** step 1 and 1b depend on the scratch parser
`/tmp/codeg-acp/211710-9bc33f50/opencode/tl-refs.py`, which is **not committed** — if it is gone,
Facts 1a and 1b cannot be re-derived until it is rewritten from the contract in Fact 1b. Facts 2
through 7 have no such dependency.

### Order of importance when time is short

1. **Fact 1a** (49 fields / 7 references on `message`) — every companion table, and therefore the
   whole schema shape, is derived from these seven. If the wire shape changes, everything
   downstream is wrong.
2. **Fact 6** (the six-method allowlist) — this decides what is *provable* without contacting a
   real account. Widening it trades a skipped test for a possible account, so it must be checked
   before anyone concludes a gate "cannot fire".
3. **Fact 3** (35 companions) — the denominator for "does every companion table have a model?".
4. Facts 2, 4, 5, 7 — stable and cheap; drift here is unlikely to mislead anyone.