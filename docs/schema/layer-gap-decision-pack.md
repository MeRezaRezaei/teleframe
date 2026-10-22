# Layer-Gap Decision Pack — the 23 methods

**Status:** measurement only. Nothing in this document was decided here, and nothing in
`src/` was changed to produce it. The choice between accepting the gap and widening the
wire is the operator's.

## The problem, in one paragraph

The wire speaks MTProto **Layer 227** (`EncryptedConnection::LAYER`) while the schema
catalogue documents **Layer 229**, and the split is deliberate. A consequence is that 23
catalogued methods are declared by the 229 catalogue and absent from the 227 wire mirror,
so `WireSchemaCoverage::classify()` returns `NOT_IN_WIRE_MIRROR` and `Client::call()`
refuses each one **before any socket is opened**. `tests/Live/LiveLayerGapReportTest.php`
reports that list and deliberately reaches no verdict, because "is being unreachable a
defect or a decision" is a product call. This pack answers the narrower, factual question
the report leaves open — **which of the 23 could ever be settled by a live probe** — so the
operator can make the decision from evidence instead of from intuition.

## What "probe-able" means here

A method is **probe-able** iff its **declared response type exists at Layer 227**. If the
response type is absent at 227, no server speaking 227 can ever produce it, so no account,
no traffic volume, and no number of attempts can turn that method into information.

Two honest limits on the term, stated so the table is not over-read:

1. **Probe-able is necessary, not sufficient, for usefulness.** All 23 methods remain
   refused pre-I/O today regardless of this table — their *method* constructors are absent
   from `api_full.tl` too. Probe-able means the response *channel* exists at 227, i.e. a
   227-era server could in principle return something meaningful; it does not mean the
   method is dialable today.
2. **It cuts across namespaces.** The grouping in the report test
   (`auth` / `communities` / `ephemeral` / `messages`) is operator-facing, but it is *not*
   the probe-ability boundary. See "Prior claim" below.

## Where layer membership comes from

Layer membership is recorded in this repo **at file granularity, not per type**. Each `.tl`
source is a single-layer snapshot and declares its layer in a `// LAYER n` line. So "is
type X at layer 227" resolves as "is X declared in the file that declares `// LAYER 227`".
There is no per-type layer table in this repository, and none is needed — the file marker is
the authority.

| Layer | File | Marker | Corroborating machine-readable record |
| --- | --- | --- | --- |
| 229 (catalogue) | `src/Schema/schema/sources/api.tl` | `// LAYER 229` at **line 3117** | `src/Schema/schema/schema-manifest.json` → `"layer": 229`; `src/Schema/schema/methods-mtproto.json` → `"layer": 229`, `"layers": {"schema": 229}` |
| 227 (wire mirror) | `src/Schema/schema/sources/api_full.tl` | `// LAYER 227` at **line 3039** | `src/Schema/Generated/schema-manifest.json` → `"layer": 227`, `"TL_telegram_v227.tl"`; `src/Schema/schema/methods-mtproto.json` → `"wire.mirror_layer": 227` |

The shipped code reads those same two files, via
`WireSchemaCoverage::layerOf()` (`src/Schema/WireSchemaCoverage.php:470`), which `max()`es
every `// LAYER ` line in the file. Verified against the real class, not a re-implementation:

```
layerOf(api.tl)      = 229
layerOf(api_full.tl) = 227
layerOf(mtproto.tl)      = 0
layerOf(mtproto_full.tl) = 0
```

The two service files declare **no** layer. `0` means *unknown* — the class's own docblock
says so — and it does not affect this analysis, because none of the 23 is a service method.

`WireSchemaCoverage::CATALOGUE_SOURCES` = `api.tl` + `mtproto.tl`; `WIRE_MIRROR_FILES` =
`api_full.tl` + `mtproto_full.tl`. That file-pair split **is** the 227-vs-229 distinction,
and it is the same split `TLRegistry` seeds its encoder from.

## The 23, classified

Evidence column names the declaring constructor and `file:line` that proves presence or
absence at 227. Every response type below was located by name in the committed sources; no
verdict is from memory of Telegram's history.

| # | Method | Declared response type | Probe-able | Evidence |
| --- | --- | --- | --- | --- |
| 1 | `auth.finishFirebasePnvLogin` | `auth.Authorization` | **YES** | `auth.authorization` — `api_full.tl:232` |
| 2 | `auth.firebasePnvSignUp` | `auth.Authorization` | **YES** | `auth.authorization` — `api_full.tl:232` |
| 3 | `auth.initFirebasePnvLogin` | `auth.FirebasePnvIntent` | **NO** | absent at 227; declared only at 229 as `auth.firebasePnvIntent` — `api.tl:2274` |
| 4 | `communities.create` | `Updates` | **YES** | `updatesTooLong` — `api_full.tl:482` |
| 5 | `communities.getJoinedCommunities` | `messages.Chats` | **YES** | `messages.chats` — `api_full.tl:288` |
| 6 | `communities.getParticipantJoinedChats` | `communities.ParticipantJoinedChats` | **NO** | absent at 227; only `communities.participantJoinedChats` — `api.tl:2236` |
| 7 | `communities.getPeerLinkRequests` | `communities.PeerLinkRequests` | **NO** | absent at 227; only `communities.peerLinkRequests` — `api.tl:2232` |
| 8 | `communities.toggleAllPeerLinkRequestApproval` | `Bool` | **YES** | `boolFalse` — `api_full.tl:1` |
| 9 | `communities.toggleCommunityCollapsedInDialogs` | `Updates` | **YES** | `updatesTooLong` — `api_full.tl:482` |
| 10 | `communities.toggleParticipantBanned` | `Bool` | **YES** | `boolFalse` — `api_full.tl:1` |
| 11 | `communities.togglePeerLink` | `Bool` | **YES** | `boolFalse` — `api_full.tl:1` |
| 12 | `communities.togglePeerLinkRequestApproval` | `Bool` | **YES** | `boolFalse` — `api_full.tl:1` |
| 13 | `ephemeral.deleteAllWelcomeMessages` | `Bool` | **YES** | `boolFalse` — `api_full.tl:1` |
| 14 | `ephemeral.deleteMessage` | `Bool` | **YES** | `boolFalse` — `api_full.tl:1` |
| 15 | `ephemeral.deleteWelcomeMessage` | `Bool` | **YES** | `boolFalse` — `api_full.tl:1` |
| 16 | `ephemeral.editMessage` | `Updates` | **YES** | `updatesTooLong` — `api_full.tl:482` |
| 17 | `ephemeral.getCallbackAnswer` | `messages.BotCallbackAnswer` | **YES** | `messages.botCallbackAnswer` — `api_full.tl:843` |
| 18 | `ephemeral.getWelcomeMessages` | `ephemeral.WelcomeMessages` | **NO** | absent at 227; only `ephemeral.welcomeMessagesNotModified` — `api.tl:2271` |
| 19 | `ephemeral.reportMessage` | `ReportResult` | **YES** | `reportResultChooseOption` — `api_full.tl:1982` |
| 20 | `ephemeral.sendMessage` | `Updates` | **YES** | `updatesTooLong` — `api_full.tl:482` |
| 21 | `messages.composeRichMessageWithAI` | `messages.ComposedRichMessageWithAI` | **NO** | absent at 227; only `messages.composedRichMessageWithAI` — `api.tl:2240` |
| 22 | `messages.requestChatJoinWebView` | `WebViewResult` | **YES** | `webViewResultUrl` — `api_full.tl:1544` |
| 23 | `messages.translateRichMessage` | `messages.TranslatedRichMessage` | **NO** | absent at 227; only `messages.translatedRichMessage` — `api.tl:2238` |

## The two counts

**17 probe-able / 6 not probe-able / 23 total.**

| Namespace | Total | Probe-able | Not probe-able |
| --- | --- | --- | --- |
| `auth` | 3 | 2 | 1 |
| `communities` | 9 | 7 | 2 |
| `ephemeral` | 8 | 7 | 1 |
| `messages` | 3 | 1 | 2 |
| **Total** | **23** | **17** | **6** |

The count of 23 itself was confirmed, not assumed: the 23 pinned names in
`tests/Live/LiveLayerGapReportTest.php:66-98` (3 + 9 + 8 + 3) match exactly the 23 methods
in `methods-mtproto.json` whose `wire.blocked_by` is `not-in-wire-mirror`.

## The prior claim did NOT hold up

The report test's docblock asserts (`tests/Live/LiveLayerGapReportTest.php:34-39`):

> `communities.*` and `ephemeral.*` do not exist at Layer 227 AT ALL … `messages.*` and
> `auth.*FirebasePnv*` DO exist at 227 with different signatures.

Measured against the committed sources, **this split is refuted in both directions**:

- **`communities.*` / `ephemeral.*` are mostly probe-able, not absent.** 7 of 9
  `communities` and 7 of 8 `ephemeral` return types that exist at 227. Only
  `communities.getParticipantJoinedChats`, `communities.getPeerLinkRequests` and
  `ephemeral.getWelcomeMessages` are genuinely 229-only. Statements #4–#16 above are direct
  counter-examples: `Bool` is declared at `api_full.tl:1`, `Updates` at `api_full.tl:482`,
  `messages.Chats` at `api_full.tl:288`.
- **`messages.*` is mostly NOT probe-able — the opposite of the claim.** 2 of 3 are 229-only
  (`messages.composeRichMessageWithAI`, `messages.translateRichMessage`). Only
  `messages.requestChatJoinWebView` returns a type present at 227 (`WebViewResult`,
  `api_full.tl:1544`).
- **`auth.*FirebasePnv*` splits the other way.** 2 of 3 are probe-able
  (`auth.Authorization` is at `api_full.tl:232`); `auth.initFirebasePnvLogin` is not, because
  `auth.FirebasePnvIntent` only appears at `api.tl:2274`.

**What this means.** The report test's prose groups these 23 by *namespace*, which reads as
though namespace predicts reachability. It does not. Probe-ability tracks the **response
type**, and that cut crosses every namespace boundary. The consequence is practical: 17 of
the 23 could, in principle, be settled by a live probe; the report's framing would have led
the operator to believe that only the 6 `messages`/`auth` ones are.

Note that the report test is not wrong about what it *reports* — it accurately derives and
pins 23 and accurately refuses to conclude. The claim at issue is confined to its docblock
prose (`:34-39`) and the mirror-image sentence written into the report body
(`the_gap_is_reported_grouped_and_the_open_question_is_left_open`). This pack does not change
either; it records where the evidence actually falls.

## What each choice costs

### (a) ACCEPT the gap

**What you get.** The 23 stay refused pre-I/O, by name, with a message that names both
layers (`WireSchemaCoverage.php:152-163`). No wire change, no `EncryptedConnection::LAYER`
change, no re-encoding risk, no migration for existing sessions. `LiveLayerGapReportTest`
keeps reporting 23 with no verdict — a stable, honest, self-consistent state.

**What it costs.** Six capabilities that Telegram's 229 schema advertises stay permanently
unavailable to this client: `auth.initFirebasePnvLogin`, `communities.getParticipantJoinedChats`,
`communities.getPeerLinkRequests`, `ephemeral.getWelcomeMessages`,
`messages.composeRichMessageWithAI`, `messages.translateRichMessage`. Firebase-PNV login is
the one with plausible near-term demand — `auth.finishFirebasePnvLogin` and
`auth.firebasePnvSignUp` are usable, but you cannot begin the flow, so the pair is
unreachable as a unit. The remaining 17 probe-able methods also become permanent *non-*
decisions: each time someone asks "should this work?", there is no evidence path to an
answer, because no live run can exercise them.

**What it does not cost.** Nothing about correctness or safety. A method absent from the
227 mirror is one no 227-era server implements, so refusing it is accurate, not a
limitation being papered over.

### (b) WIDEN the wire toward 229

**What it breaks, concretely.**

1. **The deliberate 227-vs-229 split.** `api.tl` (229) and `api_full.tl` (227) would have to
   stop being different snapshots. `WireSchemaCoverage::CATALOGUE_SOURCES` vs
   `WIRE_MIRROR_FILES` would collapse to one pair, and `classify()`'s
   `NOT_IN_WIRE_MIRROR` branch (`src/Schema/WireSchemaCoverage.php:196`) would become dead
   code. The class's entire reason for existing goes with it.
2. **`EncryptedConnection::LAYER`.** Changing it is explicitly out of bounds here and is the
   single highest-risk edit in the repo — it is the value every connection advertises in
   `invokeWithLayer`, and every committed `SessionData` was negotiated against 227.
3. **Every existing Layer-227 deployment.** A 229 advertisement from a session negotiated
   at 227 is a protocol mismatch on live infrastructure. This is not a source-compat break;
   it is a wire-compat break, and the deployed instances are the exposure.
4. **The wire mirror's own integrity.** `TLRegistry` seeds from `api_full.tl`; replacing it
   with a 229 file re-opens the encoder closure for all 823 catalogued methods at once, not
   for these 23. Blast radius is the whole catalogue, and the 23 are the visible tip of it.
5. **It does not even buy the 6.** For `auth.initFirebasePnvLogin`, the two `communities`
   getters, `ephemeral.getWelcomeMessages` and the two `messages` rich-message methods, the
   response types themselves are 229-only. Making the *request* dialable on a 227-era
   server buys a method the server cannot answer.

## Recommendation

**The evidence supports (a), ACCEPT the gap** — but the reasoning is not "17 are unreachable
anyway." It is:

- The gap is one number, in one place, and is already reported honestly by a gate that
  refuses to conclude. Accepting it costs a documented capability list; widening it costs a
  wire-compat change against live deployments.
- **Widening the wire would not make the 6 probe-able**, so the option that looks like it
  unlocks the most actually unlocks the least: 6 of 23 remain unanswerable under it.
- Of the 17 that are probe-able, the genuinely load-bearing one is `auth.initFirebasePnvLogin`
  — and it is in the **not** probe-able 6. The Firebase-PNV pair is unreachable as a unit
  because the *first* call's response type is 229-only, which is a cleaner reason to revisit
  Firebase-PNV than "the layer moved."

**This is a recommendation to the operator, not a decision taken here.** The two things
worth settling before the choice is made:

1. **Whether Firebase-PNV login is wanted.** It is the only gap member with a plausible
   operator-facing demand, and the answer decides whether any wire work is ever justified.
2. **Whether the report test's docblock prose should be corrected** (`:34-39`) to match the
   measured 17/6 split. It is documentation only — no behaviour, no gate, no test
   expectation — but as written it will mislead the next reader about which of the 23 are
   knowable.

If the answer to (1) is yes, the honest next step is a 229-compatibility investigation scoped
to the `auth.*FirebasePnv*` family, not a wholesale widening of `EncryptedConnection::LAYER`.

## Reproduction

No production code was modified. Classification was derived from committed sources only:

- layer numbers: `WireSchemaCoverage::layerOf()` read against `src/Schema/schema/sources/*.tl`
- the 23: `methods-mtproto.json` → `wire.blocked_by == "not-in-wire-mirror"`, cross-checked
  against the pinned list at `tests/Live/LiveLayerGapReportTest.php:66-98`
- response types: the `return` field of each of those 23 entries in
  `src/Schema/schema/methods-mtproto.json`
- type membership: each `return` value located as a declared type name in the pre-`---functions---`
  section of `api_full.tl` (the `// LAYER 227` file) and of `api.tl` (the `// LAYER 229` file)

No test was added, and no full suite was run.