# Framework Layers — Gap-Mining & Analysis (2026-09-07)

> Produced per the vision's planning rule (gap-mining + friction-mining BEFORE
> any layer plan). Sources inspected first-hand: teleframe src+vendor,
> teleclient src+docs, Nutgram (labeled [Nutgram]), Laravel framework vendor
> (FW). Upstream: `2026-09-07-teleframe-vision-verbatim.md` (12-layer index).
> **The OPEN QUESTIONS sections gate all Phase-5+ plans — owner must decide
> each before that layer's spec is written.**

Path legend: TF = teleframe/src, TC = teleclient/src, FW = vendor/laravel/framework/src/Illuminate, NG = /tmp/opencode/nutgram/src.

---

## I. UPRATE ROUTING + LOOP PREVENTION (vision nav #2,3)

### Concept map (Laravel → exists → gap)

| Laravel concept | Exists today | Gap |
|---|---|---|
| Route declaration (Router::get/post, FW/Routing/Router.php:158-230) | Ops-level only: `RouteTable::set()` Redis hash (TC/Bus/RouteTable.php:38); poller `filter()` + `$onUpdate` closures (TF/Laravel/Services/UpdatePollerService.php:110,540) | No dev-facing declarative vocabulary — no `Update::on(...)`, no route file, code never declares routes |
| Route compilation/caching (CompiledRouteCollection; route:cache) | `HotReloadRouter` snapshot (TC/Bus/HotReloadRouter.php:39-66; deleted in Phase 4 — unwired); consumer re-reads hash per entry (TC/Bus/IngestConsumer.php:129) | No compile step, no cache artifact; priority = accidental Redis hash insertion order |
| Route matching + param binding | Prefix-only `updateNewMessage*` match, zero-regex (RouteTable.php:53-66) | No param capture ("/start {arg}"), no constraints, no chat-type/account scoping, no 404 semantics |
| Middleware pipeline (FW/Routing/Pipeline.php onion) | One alias `tg.miniapp` (TF provider:56); sink = single slot not chain | No onion/groups/aliases — Phase 3's 26-line chain fills this |
| FormRequest validation (DI-triggered, FW/Foundation/Http/FormRequest.php:141-243) | Raw arrays only (`UpdateSinkInterface::handle`); spatie DTOs generated but unbound | No uprate validation object, no failure semantics |
| Controller resolution + DI | Closures only; RouteTable targets are stream names, not callables (RouteTable.php:27) | `[Controller::class,'method']` never becomes an invocation; no method-param injection |
| Response return path | Outbound exists (BotClient::sendMessage, UserAccountScope::call); `ingestResponse` dedups (UpdateIngestor.php:241-269) — **but nothing links them**; sink returns bool, consumer discards results | No "return becomes reply"; no exception→response mapping; account context dropped between intake and handler |
| Route model binding | Mirror IS binding-ready (identity anchors, `UpdateStored` carries root model) | No update→model bridge, no `findTF`, no UrlRoutable analog |
| Rate limiting | FLOOD_WAIT backoff only (UpdatePollerService.php:127-141) | No named limiters, no per-route/user/chat throttle; redis (already owned) unused |

### Loop-prevention design space (core subtlety)

**`out=true` CANNOT distinguish framework-sent from human-owner-typed-on-phone** — this forces a registry. Key facts: MTProto `random_id` (TL:2439) returns `updateMessageID{id,random_id}` (TL:315) — **the mirror already persists random_id→msg_id rows** (TlUpdateUpdateMessageID, migration `_create_tl_message_`:26,42-46, `_create_tl_update_table_:721`); Bot API sends return message_id directly (BotClient.php:114); bot↔bot conversations loop without any marker.

Placement options: (a) poller/sink level — drops before stream, but violates two-layer separation (mirror must stay truth-complete); (b) consumer level, default-on before fan-out — mirror intact, single choke point; (c) route-level built-in first middleware — most faithful to "registered-to-respond → eliminated"; (d) **send-time registry** written in `TeleframeClient::dispatch()`/scopes at send ((account_id, random_id, peer, msg_id?, sent_at, route_that_sent_it?)) — the ONLY option that survives the phone-typing subtlety; likely (d)+(b) or (d)+(c) as pairs.

### Uprate construction options

(a) pre-ingest raw uprate (route on raw `_`, handler may out-run mirror); (b) post-`UpdateStored` mirrored uprate (model in hand; replay-fires on re-ingest — needs dedup key); (c) two-stage: raw match now + model hydration via DI in handler (Laravel's own split). Fields: update array, account_id, source, mirrored model, ts, self_originated verdict + route attribution, keyboard identity (reserved).

### Frictions to dissolve

1. Two parallel mutually-exclusive hook mechanisms (Laravel-event vs Redis paths, different payloads)
2. Docware bootstrap — `teleproto:poll` pretty-prints and discards; no "update → my code ran" path
3. No testing surface (no Fake RunningMode; live gates only)
4. Routing is ops (Redis strings), not code — not reviewable, no route:list
5. Self-echo hazard with zero tooling (`out` column never consulted)
6. At-least-once replays reach handlers with no framework dedup identity
7. Raw-array ergonomics — hand-destructuring, no param extraction
8. No return/reply path — handler results discarded, account context lost

### OPEN QUESTIONS (gate Phase 5a spec)

| # | Question | Options |
|---|---|---|
| Q1 | Routes source of truth? | (a) in-code declarations compiled INTO Redis hash (code wins, hash=cache) / (b) Redis stays truth, code writes it / (c) code-only in-process routing, bus forwarding becomes detail |
| Q2 | Loop-elimination placement? | (a) consumer default-on / (b) built-in first middleware / (c) send-time registry + consumer check pair |
| Q3 | Registry key? | (a) (account,random_id) MTProto-first / (b) (account,peer,msg_id) covers Bot API / (c) both, random_id early + msg_id late (two-phase like Telegram itself) |
| Q4 | Uprate construction? | (a) pre-ingest raw / (b) post-UpdateStored mirrored / (c) two-stage raw-match + DI hydration |
| Q5 | Handler execution? | (a) in-consumer sync (reuses retry/DL) / (b) forward to app stream + worker / (c) sync fast-path + `->deferred()` escape |
| Q6 | Response contract? | (a) void, explicit facade replies / (b) return array → auto-reply same account+peer / (c) full Laravel Response reuse — same controller web+TG (vision endgame) |

---

## II. IDENTITY / LARAVEL BINDINGS (vision nav #5,6,8)

### Concept map

| Vision concept | Exists today | Gap |
|---|---|---|
| `User::findTF(tg_id)` | Mirror: `Teleclient::user(accountId, tgId)` → TlUser+currentInstance (TC/Teleclient.php:60, EntityAggregator.php:59-155) | No binding store whatsoever (zero Sanctum/guard refs); tenancy contract forbids bare cross-tenant lookup; `tl_id` UNINDEXED (sequential scans, migration `_create_tl_user_user_:57`); facade hides chat()/channel() |
| `HasTelegram` (contactable via our TG) | Substrate: anchor existence + contact/mutual_contact flags + access_hash (TlUserUser.php:28-64); Laravel precedent: Notifiable trait-pair + `routeNotificationFor*` + ChannelManager (FW/Notifications/); engines exist (UserAccountScope::sendMessage, BotClient::sendMessage) | No contact-channel resolution (which account/bot CAN message them); no Telegram notification channel; no trait/relation/envelope |
| `HasUserTelegram` (logged in via our user app) | Login machinery complete (TeleframeAuthService phone/2FA/QR; SessionData.userId; TeleframeClient::user factory) | The binding itself: no table/trait/event binds Laravel user_id ↔ (account_id, tl_user_id); sessions are env strings, never per-user persisted |
| Auth trio → guards | Engine scopes map 1:1: UserAccountScope / BotAccountScope (MTProto bot w/ user surface, BotAccountScope.php:20-44) / BotClient HTTP; transport auto-pick dispatch() | No Guard/UserProvider implementations, no auth.php registration, no session-per-user store; mini-app auth stays a request attribute, never an authenticated User |
| Mini-app hosting (Vue in Laravel) | InitData HMAC verify EXISTS (VerifyMiniAppInitData.php:20-96, `tg.miniapp` alias) | No freshness check (auth_date → infinite replay window); env() fallback breaks config cache; **no Vue/webapp asset exists in teleclient — the vision's "🌱 pattern exists" row is NOT backed by committed code**; no Vite/Blade host |

### Binding-table options (the 4th identity axis)

(a) **package-owned `tl_user_bindings`** (morph user nullable, tl_user_id bigint, account_id nullable, unique (tl_user_id, account_id)) — works with NO Laravel User; Sanctum analogy. (b) app-owned migration + interface — no shared findTF. (c) both + override interface. Constraints: key on stable telegram id (bigint) never anchor UUID; binding must outlive instance deletion (flag `contact_lost`, no cascade); write path hooks UpdateStored or login completion.

### Frictions

F1 aggregator returns anchor+relation not profile view (no accessors on TlUser). F2 no reverse lookup + unindexed forward. F3 sessions are per-account env credentials not per-user. F4 mini-app HMAC: no freshness + config-cache-hostile token probe. F5 facade asymmetry (user only). F6 UpdateStored Laravel-fatal (Phase 0 kills). F7 two update events with different payloads, no request shape. F8 Vue precedent absent from repo (planning must not assume it).

### OPEN QUESTIONS (gate Phase 5b spec)

| # | Question | Options |
|---|---|---|
| Q7 | findTF tenancy? | (a) primary-account default + explicit accountId override / (b) cross-tenant identity registry (breaks no-global-lookups contract deliberately) / (c) scan-all |
| Q8 | Binding ownership? | (a) package tl_user_bindings nullable morph / (b) app-owned + interface / (c) both |
| Q9 | HasTelegram sender resolution? | (a) one global sender / (b) per-binding preferred recorded at contact / (c) live resolve any contact-flagged account, fallback bot / (d) routeNotificationForTelegram override (Laravel-native) |
| Q10 | Guard shape? | (a) RequestGuard wrapping initData→binding→User / (b) attribute stays, findTF at controller discretion / (c) two guards: tg-webapp + tg-session |
| Q11 | Dual-platform service input? | (a) Uprate DTO from UpdateStored (truth-first, D8) / (b) DTO from raw TelegramUpdateReceived / (c) Phase-3 handler + web dispatcher + shared validators |
| Q12 | Mini-app frontend? | (a) ship Vite preset + Blade host + web-app.js bridge + theme vars as publishable stubs / (b) docs-only recipe / (c) defer post-Phase-4 |

---

## III. KEYBOARDS / TEMPLATES / STAGES / BOT MAP (vision nav #9-12)

### Keyboards

| Concept | Exists | Gap |
|---|---|---|
| Builders | InlineKeyboard/ReplyKeyboard fluent (TF/Core/Types/InlineKeyboard.php:28-123) | Anonymous value objects — no id, no registration, no key→action table; 64-byte callback_data limit unenforced |
| Update→keyboard attribution | [Nutgram] InlineMenu `data@method` string equality (NG/Conversations/InlineMenu.php:100-151) | Not a first-class object; regex matching banned here (zero-regex); no provenance |
| Injection prevention | Webhook secret only (TelegramWebhookController.php:30-35); HMAC precedent (VerifyMiniAppInitData:84-87); `ext-sodium` + `VaultCrypto` now live in-repo (Phase 2 merged teleclient; `composer require sodium` satisfied) | Cleared 2026-09-08 |

Vectors: forged callback_data from modified clients; replay (old keyboards clickable forever); cross-chat relay. Format options: (a) plain registry ids scoped user+msg; (b) opaque server-side handle (revocable, stateful); (c) HMAC-signed compact `v1:<kid>:<keyIdx>:<arg>:<sig8-10B>` — stateless, bind msgId+chatId into MAC, truncated MAC acceptable for button integrity. Identity needs: deterministic content-hash id (stable across deploys) + registered key→action = the uprate route + version byte for rotation ("menu expired" via answerCallbackQuery, BotClient:640-652).

### Message templates (Blade-for-Telegram)

Blade mechanics all present in vendor (CompilerInterface, Compiler:92-147 xxh128 path + mtime expiry + atomic replace, BladeCompiler:181-330, CompilerEngine:60-96, FileViewFinder) — directly mirrorable. **Compile target already built**: `EntityParser::htmlToEntities/markdownToEntities` (TF/Core/Entities/EntityParser.php:23-58, zero-regex UTF-16 scanner). Gaps: no MessageCompiler, no bootstrap/cache usage anywhere in TF src, no schema-layer invalidation (mtime-only expiry would survive layer bumps — must salt with `Teleframe::schemaLayer()`), no typecheck vs `MethodRegistry::get('sendMessage')` param schema.

Options: syntax = plain PHP returning `{text, entities, reply_markup?}` (recommended — zero new parser) vs `@tg` directive tokenizer (zero-regex burden) vs JSON matrix+places; cache = entity plan (precomputed offsets) + closure fallback for media.

### Stage machine

[Nutgram] Conversation precedent: steps=method names (imperative), serialized-object state (breaks on refactor, closure dep) — both anti-patterns here. Laravel side: FormRequest redirect-shaped failures (FormRequest.php:164-190) must become template-shaped per-stage, while FINAL submit reuses the identical FormRequest (that's what makes it "the same route"). State home: PSR-16 array default + redis adapter (Phase 2 brings Bus redis) as plain arrays `{stageSet, currentStage, data[], msgIds[]}` — not serialized objects. Response flag: container-bound TelegramContext (fits D2) vs request attribute (miniapp precedent) vs route param. Final submit: in-process `Request::create` sub-dispatch vs real internal HTTP. **Dispatch precedence must be defined once**: registration-echo no-op → active stage → keyboard object → general handler (friction F5).

### Bot map

Anchors: MethodRegistry (TF/Core/Schema/MethodRegistry.php:15-87) is the pattern; `bot('token')` exists but anonymous (docs/bot-client.md:17); BotClient hard-wires api.telegram.org; webhook controller = inbound exposure precedent. Gaps: named-bot resolution (`BotMap::for('storebot')->priceList()`), capability manifest (getMyCommands probe at registration, BotClient:674-677 + manual manifest), token vault, outbound HTTP-API exposure + caller auth, bot-on-bot/user-to-bot transport (greenfield).

### OPEN QUESTIONS (gate Phase 5c-f specs)

| # | Question | Options |
|---|---|---|
| Q13 | Template syntax? | (a) plain PHP + EntityParser (rec) / (b) @tg directives / (c) JSON declarative |
| Q14 | Cache artifact? | (a) entity plan + closure fallback (rec) / (b) closure only |
| Q15 | callback_data format? | (a) HMAC compact truncated / (b) opaque handle / (c) plain scoped ids |
| Q16 | Keyboard id lifecycle? | (a) content-hash stable / (b) per-deploy rotation |
| Q17 | Stage state home? | (a) PSR-16 + redis adapter (rec) / (b) tl_stage_sessions table / (c) hybrid pointer+web-route |
| Q18 | Telegram-context flag? | (a) container TelegramContext / (b) request attribute / (c) route param |
| Q19 | Bot capability discovery? | (a) getMyCommands probe + manifest (rec) / (b) pure manual / (c) convention |
| Q20 | Final-stage submit transport? | (a) in-process sub-request / (b) real internal HTTP |

---

## RULINGS (Q1–Q20) — decided 2026-09-07 under the Autonomy Protocol

Per the vision's autonomy protocol (owner verbatim, vision doc §Autonomy):
these are PATH decisions — the owner reviews results at spec/phase gates, not
option menus. Two rulings touch developer-facing surface (Q7, Q13) and are
flagged veto-able at that layer's spec review without rework.

| Q | Ruling | Rationale (goal-fit) |
|---|---|---|
| Q1 | **(a) In-code declarations, compiled into the Redis hash (code wins, hash = cache)** | Dissolves friction I.4 (routing-is-ops); Laravel pattern; hash keeps hot-reload ops story |
| Q2 | **(d)+(b): send-time registry + default-on built-in first middleware (elimination), per-route escape hatch** | Only pair surviving the phone-typing subtlety; faithful to "registered-to-respond → eliminated"; mirror stays truth-complete — **ENACTED Phase 3** (`EchoEliminator`, `onOwn` escape hatch) |
| Q3 | **(c) Two-phase: random_id early, msg_id reconciled late** | Mirrors Telegram's own mechanism; mirror already persists the mapping |
| Q4 | **(c) Two-stage: raw match at router, model hydration via DI in handler** | Laravel's own split (match-then-bind); keeps consumer lean; replay-safe — **ENACTED Phase 3** (`Update::constructor()` eager; lazy DI hydration) |
| Q5 | **(a) In-consumer synchronous, reusing retry/DL semantics** | `->deferred()` is YAGNI until a workload demands it |
| Q6 | **(a) v1: handlers return void, explicit replies via facade — (c) full Laravel Response reuse becomes the Phase 5e contract** | Zero magic first; the same-controller endgame arrives with the stage machine that needs it — **ENACTED Phase 3** (facade `send()`/`run()`) |
| Q7 | **(a) findTF: primary-account default + explicit accountId override** ⚠ dev-facing | Preserves the tenancy contract (no silent cross-tenant registry) |
| Q8 | **(a) Package-owned `tl_user_bindings`, nullable morph** | Works standalone AND in Laravel (Sanctum analogy) |
| Q9 | **(d) `routeNotificationForTelegram()` override + (a) configured default sender fallback** | Laravel-native override point; sane zero-config default |
| Q10 | **(c) Two guards: `tg-webapp` (initData) + `tg-session` (our user-app sessions)** | Maps 1:1 to the vision's auth trio without overloading one guard |
| Q11 | **(a) Uprate DTO delivered through Q4(c) mechanics — truth-first (D8)** | Consistent with two-stage: raw + account + verdict on the uprate, models via DI |
| Q12 | **(a) Ship publishable stubs: Vite preset + Blade host + web-app.js bridge + theme vars** | The vision wants the pattern REAL; research proved no precedent exists to reuse |
| Q13 | **(a) Plain PHP templates returning `{text, entities, reply_markup?}` via EntityParser** ⚠ dev-facing | Zero new parser = zero-regex-safe; Laravel PhpEngine-consistent |
| Q14 | **(a) Cache the compiled entity plan; closure fallback for media/albums** | Fast path for text; full coverage where entities are insufficient |
| Q15 | **(c) HMAC-signed compact callback: `v1:<kid>:<keyIdx>:<arg>:<sig≤10B>`, msg+chat bound into MAC** | Stateless, replay-scoped, fits 64B; ext-hash available now |
| Q16 | **(a) Content-hash keyboard ids, stable across deploys** | Menus survive deploys; rotation via version byte |
| Q17 | **(a) PSR-16 seam (array default) + redis adapter; plain-array state, never serialized objects** | Matches unification D2 seam; avoids Nutgram's refactor-breaking state |
| Q18 | **(a) Container-bound `TelegramContext`** | Fits PSR-11 delegate (D2); detectable above controllers (constraint 8) — **ENACTED Phase 3** (`TelegramContext`, bound per dispatch) |
| Q19 | **(a) getMyCommands probe at registration + manual capability manifest** | Automates the discoverable; documents the rest |
| Q20 | **(a) In-process `Request::create` sub-dispatch for final stage submit** | No HTTP round-trip; flag carried in container; loop-safe per Q2 registry |

---

## Hard constraints surfaced (bind ALL layer designs)

1. **Zero-regex** in src — Nutgram's matcher unportable; all matching = exact-map + sscanf-style.
2. **64-byte callback_data budget** — full HMAC hex alone exhausts it.
3. ~~**ext-sodium split-brain** until Phase 2 merge~~ — **CLEARED** 2026-09-08: `ext-sodium` + `VaultCrypto` / `InMemoryVault` now live in-repo (`MeRezaRezaei\Teleframe\Backup\*`); `VaultCryptoTest` proves it. keyboards/templates use ext-hash HMAC meanwhile (unchanged).
4. **mtime-only cache expiry is insufficient** — every compiled artifact salts with schemaLayer().
5. **Tenancy contract** (no global lookups by tg id) — findTF must choose a deliberate breach or default-account strategy.
6. **At-least-once delivery + replay-firing events** — uprate identity/dedup key is a prerequisite of every handler layer.
7. **Mini-app Vue precedent does not exist in the repo** — Phase 5g must build it, not "wire it".
8. **Webhook always-200 contract** — telegram-context response interception happens above controllers.
