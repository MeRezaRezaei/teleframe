# tgweb-patches — relay patches for the vendored telegram-web client

Patches in this directory are applied on top of the bare `tgweb/` checkout
(upstream `Ajaxy/telegram-tt`, HEAD `9cb10b2`), by
`bin/build-tgweb.sh`, before `npm run build`. They let the browser client
connect THROUGH this package's relay instead of Telegram's own servers, gated
on an endpoint flag so the default build behaves byte-for-byte like stock.

Owned by W2 (this dir is tracked in the parent repo; `tgweb/` stays
gitignored).

## Patch list

| Patch | What it does |
| --- | --- |
| `0001-getdc-host-override.patch` | Live-endpoint override for the DC selection + connection-address path. When the endpoint flag is set, every DC resolves to the relay endpoint for the chosen transports; when it is unset the stock `zws{N}.web.telegram.org` / `apiws` / `apiw1` behavior is unchanged. |
| `0002-relay-server-key.patch` | Lets the browser client accept the package's relay RSA server key during MTProto master-key auth. When the key flag is set (`TELEGRAM_WEB_RELAY_SERVER_KEY` runtime global, or the build-time bake), the relay key `{fingerprint, n, e}` is merged into GramJS's `SERVER_KEYS` map so `resPQ.serverPublicKeyFingerprints` lookup succeeds; when it is unset the stock Telegram server keys are unchanged. |
| `0003-relay-config-channel.patch` | Carries the relay config from the MAIN thread into the GramJS worker. `collectRelayConfig()` in `src/api/gramjs/worker/connector.ts` reads the `window` globals (`TELEGRAM_WEB_LIVE_ENDPOINT`, `TELEGRAM_WEB_RELAY_SERVER_KEY`) and the `import.meta.env.VITE_*` values on the main thread and passes them as `args[2]` of the `initApi` worker message; `worker.ts` seeds them into its own `globalThis` before any client is constructed; `src/lib/gramjs/crypto/RSA.ts`'s `RelayAwareServerKeys` lazily registers the relay key on first `get()` at auth time. `import.meta.env` never reaches the worker, so this channel is the delivery path. (The old "json transport" idea never shipped — see the feasibility design section below.) |
| `0004-relay-ticket-seam.patch` | Mints a one-shot relay ticket per WS dial via `obtainRelayTicket()` and appends `?ticket=` to the relay URL in `PromisedWebSockets.getWebSocketLink()` (W-02 one-shot ticket gate). |
| `0005-skip-qr-phone-gate-in-endpoint-mode.patch` | In endpoint (relay) mode, `TelegramClient.start()` returns before the stock phone/QR auth flow: the relay already granted the vault account at the WS upgrade and answers `auth.*` locally, so the browser takes authorization from the relay's answers instead of parking on the `Auth` screen. |

### 0001 — live-endpoint override (5 source files, minimal)

Flag resolution (highest wins):

1. `globalThis.TELEGRAM_WEB_LIVE_ENDPOINT` — runtime global. On the main
   thread this is `window.TELEGRAM_WEB_LIVE_ENDPOINT`; the code reads
   `globalThis`, so the same gate also works wherever GramJS executes.
2. Build-time bake is ONLY into `dist/relay-bootstrap.js`: `bin/build-tgweb.sh`
   writes `globalThis.TELEGRAM_WEB_LIVE_ENDPOINT` into a same-origin
   `relay-bootstrap.js` file and injects a `<script src="./relay-bootstrap.js">`
   before the module script, so the global is set on the main thread before the
   app boots. External same-origin scripts are allowed by the stock CSP
   `script-src 'self'` (inline scripts are not).
   `vite.config.ts` also maps `TELEGRAM_WEB_LIVE_ENDPOINT` /
   `TG_DC_HOST` to `import.meta.env.VITE_TELEGRAM_WEB_LIVE_ENDPOINT`, but that
   `import.meta.env` value NEVER reaches the GramJS worker bundle — the worker
   only sees runtime globals, which reach it over the 0003 init channel.

Changes:

- `src/lib/gramjs/Utils.ts` — `getDC()` (the single DC choke point,
  `src/lib/gramjs/Utils.ts:182` stock) now returns the relay host/port when
  the flag is set (`parseTelegramWebLiveEndpoint`), and unchanged DCs
  otherwise. Adds exported helpers `getTelegramWebLiveEndpoint()` and
  `getTelegramWebLiveUrl(transportScheme)`. All five `zws{N}` DC ids resolve
  to the one relay endpoint — the relay routes per ticket/account server-side,
  so the `downloadDC`/`-1` distinction is intentionally dropped while relayed.
- `src/lib/gramjs/extensions/PromisedWebSockets.ts` — `getWebSocketLink()`
  (`:81` stock) returns the relay URL verbatim when the flag is set and the
  endpoint is a `ws(s)` target (or a bare hostname → `wss://{host}/apiws`);
  otherwise stock `wss://{host}:{port}/apiws[_test][_premium]`.
- `src/lib/gramjs/extensions/HttpStream.ts` — `HttpStream.getURL()` (`:67`
  stock) same override for the HTTP long-poll transport (`https://{host}/apiw1`
  for a bare hostname; `http(s)` endpoints used verbatim).
- `vite.config.ts` — maps `TELEGRAM_WEB_LIVE_ENDPOINT` (or `TG_DC_HOST`) to
  `VITE_TELEGRAM_WEB_LIVE_ENDPOINT` in the `setViteEnv` block; the resulting
  `import.meta.env` constant is main-thread-only.
- `src/@types/global.d.ts` — types the new `ImportMetaEnv` key.

Scheme handling: a `wss://` endpoint only drives the WebSocket transport (the
HTTP fallback keeps its stock URL so it can still reach `apiw1`); an `https://`
endpoint only drives the HTTP transport. This mirrors the existing
dual-transport design where the WS connection is primary
(`TelegramClient.ts:319-334`: `_connection` = `ConnectionTCPObfuscated`,
`_fallbackConnection` = `HttpConnection`).

### 0002 — relay server RSA key (3 source files, minimal)

MTProto master-key auth (`Authenticator.ts` Step 2) loops
`resPQ.serverPublicKeyFingerprints` and looks each up in GramJS's `SERVER_KEYS`;
a relayed connection presents the package's own RSA key, so the client would
throw `SecurityError 'Step 2 could not find a valid key for fingerprints'`
unless that key is accepted. This patch merges the relay key into the map.

Key resolution (highest wins):

1. `globalThis.TELEGRAM_WEB_RELAY_SERVER_KEY` — runtime global. A single string
   `"fingerprint:n:e"` (colon-separated decimal strings, e.g.
   `"-1234567890123456789:2147483647:65537"`), split on `:` and parsed with
   `BigInt`/`Number` into `{fingerprint, n, e}`. On the main thread this is
   `window.TELEGRAM_WEB_RELAY_SERVER_KEY`; the code reads `globalThis`, so the
   same gate works wherever GramJS executes.
2. Build-time bake is ONLY into `dist/relay-bootstrap.js`: `bin/build-tgweb.sh`
   combines the three env vars `TELEGRAM_WEB_RELAY_SERVER_KEY_FP`,
   `TELEGRAM_WEB_RELAY_SERVER_KEY_N`, `TELEGRAM_WEB_RELAY_SERVER_KEY_E` (all
   three required together) into the runtime string `fingerprint:n:e` and sets
   `globalThis.TELEGRAM_WEB_RELAY_SERVER_KEY` in the baked bootstrap file.
   `vite.config.ts` separately maps the three to
   `import.meta.env.VITE_TELEGRAM_WEB_RELAY_SERVER_KEY_{FP,N,E}`, but that
   `import.meta.env` value never reaches the worker bundle — the worker gets
   its values over the 0003 init channel.

Changes:

- `src/lib/gramjs/crypto/RSA.ts` — gates the relay key (`getRelayServerKey`:
  the runtime global `TELEGRAM_WEB_RELAY_SERVER_KEY` beats the `VITE_*` build
  constants) and merges it into the `SERVER_KEYS` map so `encrypt()` and the
  `Authenticator.ts` fingerprint lookup resolve it exactly like the stock
  keys. 0002 merges eagerly at module load; 0003 replaces that with the lazy
  `RelayAwareServerKeys` — see below.
- `vite.config.ts` — maps `TELEGRAM_WEB_RELAY_SERVER_KEY_{FP,N,E}` to
  `VITE_TELEGRAM_WEB_RELAY_SERVER_KEY_{FP,N,E}` in the `setViteEnv` block
  (main-thread-only constants; workers never see them — see 0003).
- `src/@types/global.d.ts` — types the three new `ImportMetaEnv` keys.

Fingerprints are SIGNED bigints in this codebase (negative magnitude, matching
GramJS convention) — bake the relay fingerprint as its signed decimal form.

When neither the runtime global nor the bake is set, the map is byte-for-byte
identical to stock and the default build is unchanged. Only the stock
Telegram keys are accepted.

### 0003 — relay config channel (4 source files, minimal)

Closes the gap between flag resolution and execution: GramJS runs inside the
web worker, whose bundle never contains `import.meta.env` values and which
cannot see the main-thread `window` globals. 0003 hand-carries the resolved
relay config from the main thread into the worker over the existing `initApi`
worker message.

Flow:

- `src/api/gramjs/worker/types.ts` — new `RelayConfig` type
  (`{ webLiveEndpoint?, serverKey? }`); the `initApi` `OriginPayload` gains an
  optional third args slot (`[ApiInitialArgs, LocalDb, RelayConfig?]`).
- `src/api/gramjs/worker/connector.ts` — new `collectRelayConfig()` reads the
  `window` globals `TELEGRAM_WEB_LIVE_ENDPOINT` / `TELEGRAM_WEB_RELAY_SERVER_KEY`
  plus `import.meta.env.VITE_TELEGRAM_WEB_LIVE_ENDPOINT` ON THE MAIN THREAD, and
  `initApi()` attaches the result as `args[2]` of the `initApi` worker message.
- `src/api/gramjs/worker/worker.ts` — on `initApi`, seeds `args[2]` into its
  own `globalThis` (`Object.assign`) BEFORE constructing the client, so the
  0001/0002 `getTelegramWebLiveEndpoint`/`getRelayServerKey` gates resolve from
  that scope exactly like a main-thread runtime global.
- `src/lib/gramjs/crypto/RSA.ts` — `SERVER_KEYS` becomes `RelayAwareServerKeys`
  (a `Map` subclass) whose `get()` lazily registers the relay key — read from
  the seeded runtime global — on first call, i.e. at auth time during the
  `resPQ` fingerprint loop, replacing 0002's eager module-load merge.

Build-time bake is ONLY into `dist/relay-bootstrap.js` (see "How to build"
below); the current built worker (`worker-DkRy9E7g.js`) ships only the runtime
key gate and receives its values over this channel.

## How to build with them

LIVE GATE — a full dist rebuild requires real Telegram credentials and is NOT
performed in CI or by default:

```bash
# 1. Create credentials at my.telegram.org (the same pair the engine uses is fine).

# 2. Bake the relay endpoint into dist/relay-bootstrap.js and point it at the relay:
TELEGRAM_API_ID=12345 TELEGRAM_API_HASH=abcdef \
TELEGRAM_WEB_LIVE_ENDPOINT='wss://relay.example.com/apiws' \
./bin/build-tgweb.sh
# TG_DC_HOST='relay.example.com' is accepted as an alias for the bare-host form.

# 3. Relay config is NOT baked into the worker bundle — import.meta.env never
#    reaches it. Build bakes the globals into dist/relay-bootstrap.js, loaded
#    via <script src> from dist/index.html (main thread) — same-origin file,
#    allowed by the stock CSP `script-src 'self'` — and the 0003 init channel
#    delivers them to the worker, which seeds its own globalThis before
#    connecting.
```

Runtime-only override (no bake): host the built bundle and set
`window.TELEGRAM_WEB_LIVE_ENDPOINT` (and/or `window.TELEGRAM_WEB_RELAY_SERVER_KEY`)
before the app boots. The main thread forwards them to the GramJS worker over
the 0003 init channel (`collectRelayConfig()` → `args[2]`), and `worker.ts`
seeds its own `globalThis` — the worker cannot read `window` or
`import.meta.env` directly, so the channel is the delivery path for both baked
and runtime values.

When neither env nor runtime global is set, the bundle is stock Telegram Web A:
all DCs resolve to `zws{N}[.-1].web.telegram.org`, port 443, and transports
build stock `wss://…/apiws` / `https://…/apiw1` URLs.

## Verifying the patches

```bash
git -C tgweb apply --check ../tgweb-patches/0001-getdc-host-override.patch
```

`bin/build-tgweb.sh` runs the same checks (`git apply --check`) before applying
each patch and fails loudly on a mismatch.

Apply order matters: 0001 → 0002 → 0003 → 0004 → 0005, on a clean tgweb tree (HEAD
`9cb10b2`). `bin/build-tgweb.sh` applies them in that (lexically sorted) order
and `git apply --check`es each one before applying, failing loudly on a
mismatch.

## Server-side terminus (LANDED 2026-09-21)

The server-side terminus a patched client's transport terminates into is
LANDED (Wave C shape #2, commit `8c9f1a6e`, extended 2026-09-21 by
`RelayFactory::bridgeFor()`): a relayed vault account's scope is bound to the
client-in seam with zero dialing, zero vault reads at build, and no session
string ever crossing the seam.

- `RelayFactory::terminalFor($ctx)` — OUTBOUND `RelayMessage`s from the
  account's filter onion dispatch through the vault account client; the RPC
  reply comes back as the produced INBOUND message.
- `RelayFactory::bridgeFor($ctx)` — the client-in frame-dial seam returns a
  `RelayFrameDialBridge` whose `surface()` is an `EncryptedConnection` lazily
  seated on the SAME vault account scope the onion/terminal resolve
  (`RelayFrameDialBridge::lazy()` derives the connection + `SessionData` from
  the scope client's `getSession()` on first use). A patched client's binary
  frames terminate into it via `pumpFrame()`. Building a bridge never resolves
  the vault, never decodes a session string, and never dials — the socket
  opens lazily on the first RPC.
- Multi-account switching is unchanged in spirit:

  `RelayFrameDialBridge::swapScopeResolver()` = swap the scope resolver behind
  the ticket; the SAME bridge re-seats on the other account on next use.

The live dist rebuild / bake remains an OPT-IN gate (real
`TELEGRAM_API_ID`/`TELEGRAM_API_HASH`), is NOT part of CI, and no dial is ever
claimed without real-harness verification. Zero credentials cross these seams.

## JSON relay transport — feasibility design (shelved — distinct from patch 0003)

**Decision: implementable as a CODE patch? NO. Documented design below.**
Accuracy beats a fake patch: a JSON transport is not feasible within the
current client transport abstraction, and inventing a wire format is
explicitly against the settled contracts (do NOT invent formats — match what
EXISTS).

Why it is not feasible in scope, with file:line evidence:

1. The client's end-to-end RPC path is **binary MTProto over an obfuscated
   framing**, not JSON:
   - Default connection is `ConnectionTCPObfuscated` (transport-obfuscated
     WebSocket), HTTP long-poll as fallback — `src/lib/gramjs/client/TelegramClient.ts:127-128`.
   - Connections are built from a host/port DC object and handed to a
     `MTProtoSender` — `TelegramClient.ts:319-334` (main), `TelegramClient.ts:604-622` (exported senders).
   - Every transport encodes the auth-key-encrypted MTProto message stream via
     `Connection`/`ObfuscatedConnection` (`src/lib/gramjs/network/connection/Connection.ts:176-211`),
     the obfuscated2 handshake (`TCPObfuscated.ts:39-79`) and the abridged
     packet codec (`TCPAbridged.ts:12-64`). There is no point in this chain
     where a plaintext TL request exists as a JSON-serializable value.
2. **No client-addressable JSON endpoint exists.** The only settled network
   contracts are the account-control surface (Wave A): `POST
   /teleframe/relay/redeem` consumes a ticket once and returns
   `{ok, account_id, dc_id}` — a server-side redeem, not a stream. The Wave-B
   `RelayMessage` value object
   (`src/Teleframe/Control/RelayMessage.php`) is an in-process PHP
   abstraction (outbound/inbound, `withMethod`/`withParams` rewrite,
   `replace` short-circuit), not a wire format. The relay terminus that now
   exists server-side (see "Server-side terminus (LANDED 2026-09-21)")
   terminates BINARY MTProto frames into a vault-seated `EncryptedConnection`
   — it deliberately does NOT add a JSON endpoint.
3. The research ruling for this wave (Wave D) is the **getDC bake + patched
   transport bridging into the package's `EncryptedConnection`** — i.e. the
   client keeps speaking binary MTProto-over-WebSocket and the relay swaps the
   scoped vault session behind the ticket. A `{ticket}` → `{result|update}`
   JSON wire would require (a) inventing method-invoke + update-stream
   contracts the server does not expose, (b) a JSON endpoint the landed
   client-in terminus deliberately does not add (see "Server-side terminus
   (LANDED 2026-09-21)" — it terminates binary MTProto frames, not JSON), and
   (c) a parallel update-delivery path that bypasses the entire
   `difference`/`handleGramJsUpdate` pipeline that the UI depends on
   (`src/api/gramjs/methods/client.ts:131`). All three are out of scope here.

**Design verdict (post-terminus):** the JSON transport design is permanently
shelved. A thin `Connection`/`HttpStream`-style transport speaking a
`RelayMessage` envelope over the relay socket would still require the three
out-of-scope items above, and the terminus that now exists terminates the
client's existing binary MTProto-over-WebSocket path into a vault-seated
`EncryptedConnection` (`RelayTerminal`/`RelayFrameDialBridge` via
`RelayFactory::terminalFor()` + `bridgeFor()`) — so no new wire format is
needed or wanted: patch 0001 + the existing transports terminating into the
landed server terminus remain the correct client contract.