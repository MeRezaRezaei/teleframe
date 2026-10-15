# Usage — the LIVE GATE: proving the wire against a real account

> **Read this before you trust a green suite.** 972 methods in this package
> resolve against the committed catalogue and the TL mirror. A correct-looking
> CRC32 in a checked-in file is **not** proof that Telegram accepts them. Every
> claim in the offline suite is a claim about *this package's own consistency*.
> Nothing in it is a claim about *Telegram's answer*.
>
> This page documents the opt-in lane that closes that gap. It is **not** part
> of `composer verify`, and it is **not** part of CI.

---

## 1. The gap, measured

| Fact | Where |
| --- | --- |
| 972 methods resolve against the committed catalogue and TL mirror | the schema pipeline, on every commit |
| **0** of them have been dialled on a live connection | measured 2026-10-13 |
| **No `TELEFRAME_LIVE*` opt-in variable existed in the whole test tree** | every `TELEFRAME_*` gate was a PG or MySQL container |
| `tests/Live/` held one file, `LiveWarmFenceTest.php` | a warm-ledger and endpoint-policy fence — it constructs nothing and dials nothing |

So "capped" was not merely unmet, it was **unenforceable**. This lane is the
enforcement.

## 2. The one variable, and why it is not `TELEFRAME_LIVE`

```bash
TELEFRAME_LIVE_TESTS=1
```

**It is deliberately a different name from `TELEFRAME_LIVE`.** That one already
exists and already means something else: it is `config('teleframe.live_mode')`
(`src/Laravel/config/teleframe.php:88`), the flag **a host** sets to make its own
application dial Telegram. Reusing it for the test gate would arm a live test
lane as a side effect of configuring an app — you export it once to run
production, and a later `composer verify` silently starts spending account
quota. Two knobs that both mean "live" must not share a name.

Two supporting variables:

| Variable | Default | Meaning |
| --- | --- | --- |
| `TELEFRAME_LIVE_TESTS` | unset | `1` arms the lane. **Only a literal `1`** — `0`, `true`, `yes`, `on` do not arm it. |
| `TELEFRAME_LIVE_TESTS_SKIP` | unset | `1` disarms, **even if the force flag is set**. The explicit "not now". Mirrors `TELEFRAME_MYSQL_SKIP`. |
| `TELEFRAME_LIVE_STEPS` | unset | The steps this run is attempting. Under the force flag, a step that is not declared **fails loudly by name**. |

## 3. The rule that defines this lane: A FORCED RUN FAILS, NEVER SKIPS

`RunsLiveTelegram::liveGateArmed()` returns `true` on the strength of
`TELEFRAME_LIVE_TESTS=1` **alone** — before any socket is opened, before any
credential is read, before any other variable is consulted. That ordering is the
whole design, copied from `RunsMysqlMigrations::mysqlTrackEnabled()`.

The consequences:

| State | What happens |
| --- | --- |
| Lane not armed | the test skips, with a message that says *"not armed"* and never *"attempted and passed"* |
| Armed, step not declared | **fails**, naming the step and the way to declare it |
| Armed, step declared, variable missing | **fails**, naming the variable and what it is for |
| Armed, step declared, variable present but not a real credential | **fails** before any socket |

So a skip in the CI rollup means exactly *"this lane was not armed"* and never
*"this lane tried and could not"*.

**This is machine-checked, not promised.** `tests/Live/LiveGateContractTest.php`
runs on every `composer verify` and fails if:
- the skip API is called anywhere in `tests/Live/**` or the trait except inside
  `RunsLiveTelegram::skipUnlessArmed()` (scanned over **tokens**, so a docblock
  explaining the ban is not a violation of it);
- any live test reads the environment outside the trait;
- any file importing the gate forgets `requireLiveStep()`;
- the force flag does not arm the lane with every other live variable unset.

## 4. Running it

```bash
# Nothing to set — this is what composer verify already does. The lane is
# disarmed and every live test skips. 12 skips, all of them honest.
vendor/bin/phpunit tests/Live

# The one step that needs no account and no credential: a real dial to
# 127.0.0.1 on the discard port. Proves an ARMED run executes rather than
# skipping, and touches nothing off the machine.
TELEFRAME_LIVE_TESTS=1 TELEFRAME_LIVE_STEPS=loopback_dial \
  vendor/bin/phpunit tests/Live

# A real read against the funded account's DC.
TELEFRAME_LIVE_TESTS=1 TELEFRAME_LIVE_STEPS=wire_read,dc_dial \
  vendor/bin/phpunit tests/Live
```

The gate **never reads `.env`**. Every value comes from the process environment,
so what the gate asserts on is provably what you exported.

## 5. What a maintainer must set

### For any step that dials Telegram

| Variable | Needed by | Where it comes from |
| --- | --- | --- |
| `TELEGRAM_API_ID` | every dialling step | your api.telegram.org app |
| `TELEGRAM_API_HASH` | every dialling step | your api.telegram.org app |
| `TELEGRAM_USER_SESSION` | `wire_read`, `dc_dial`, `auth_export_login_token` | the funded account. **Must carry a 256-byte auth key** — a placeholder is refused by name, before any socket |
| `TELEGRAM_BOT_TOKEN` | `auth_import_bot_authorization` | `@BotFather` |
| `TELEFRAME_LIVE_DC_ID` | all of the above | optional, defaults to `2` |

### For the login flow, step by step

The four steps of the device-transfer flow are **separate tests** and are never
chained. `auth.signIn` needs a code a human reads off a phone, and MTProto has
no dry run. The intermediate values come from *your own* `auth.sendCode` run
and are passed in through the environment — the gate will not print them for
you, because printing them would mean writing credentials somewhere.

| Step | Extra variables |
| --- | --- |
| `auth_send_code` | `TELEFRAME_LIVE_PHONE` |
| `auth_sign_in` | `TELEFRAME_LIVE_PHONE`, `TELEFRAME_LIVE_PHONE_CODE`, `TELEFRAME_LIVE_PHONE_CODE_HASH`, `TELEFRAME_LIVE_PENDING_SESSION` |
| `auth_export_login_token` | — |
| `auth_import_login_token` | `TELEFRAME_LIVE_LOGIN_TOKEN`, `TELEFRAME_LIVE_DEVICE2_SESSION` |

`TELEFRAME_LIVE_DEVICE2_SESSION` **must be a different auth key** from the one
that minted the token. Importing into the minting session would prove nothing,
and a locally-fabricated `auth.authorization` would look identical to a real one.

### For the Passport step

| Variable | Notes |
| --- | --- |
| `TELEFRAME_LIVE_PASSPORT_DATA` | the `data` field of a real `message.passport_data.credentials`, base64, captured verbatim |
| `TELEFRAME_LIVE_PASSPORT_SECRET` | the `secret` field, base64 |
| `TELEFRAME_LIVE_PASSPORT_HASH` | the `hash` field, base64 |
| `TELEFRAME_PASSPORT_PRIVATE_KEY` | **the package's own config variable**: a path to the RSA PEM, `chmod 600` |

The PEM is read through the shipped `PassportKeyLoader`, not bypassed, so its
owner-only permission check (`src/Teleframe/Passport/PassportKeyLoader.php:83-88`)
is exercised too.

### Where the report goes

`TELEFRAME_LIVE_REPORT` if set, otherwise a temp file. The path is printed when
each section is appended. **Report bodies never contain a credential** — the
gate states which method answered and what shape it was, never a token, a hash,
a session or a decrypted value.

## 6. What the gate proves

| Step | Proves |
| --- | --- |
| `loopback_dial` | the dial path really runs and really refuses. No account, no Telegram. |
| `wire_read` | `Client::live()` + `call()` returns a **real server constructor** (`config`) and **not** the offline marker — i.e. Telegram accepted a packet this package built. Same for the `callMany()` batch path, which is a different encoder wrapper. |
| `dc_dial` | `callToHost()` reaches `149.154.x.x:443` and gets a decoded `nearestDc` with `this_dc`. This is the path the doctor command uses. |
| `auth_send_code` | `auth.sendCode` returns a `phone_code_hash` bound to the auth key this client just handshaked. |
| `auth_sign_in` | `auth.signIn` exchanges a real code for an `auth.authorization` carrying the account. |
| `auth_export_login_token` / `auth_import_login_token` | a login token minted on one device is accepted by a **second, different** session. The transfer is real. |
| `auth_import_bot_authorization` | a bot token is accepted by a real DC and comes back as `auth.authorization` whose account reports `bot: true`. **This is the first live evidence for the bot-over-MTProto path in this repository** — `VaultMtprotoAnswerer` answers every `auth.*` in memory, so nothing offline can stand in for it. |
| `passport_decrypt` | a **real captured** `message.passport_data.credentials` decrypts through the shipped decryptor: the envelope decrypts, the SHA-256 over the *padded* plaintext matched, and the independent `PassportCredentialsVerifier` agrees. A one-bit tamper is refused. |
| (ungated) | the 23 layer-gap methods are refused **before any I/O**, by name, naming both layers — and `InvalidArgumentException` vs `RuntimeException` is what proves it was pre-I/O rather than a failed dial. |

## 7. What the gate does NOT prove

Being explicit is the point of this section.

- **It does not prove the other ~950 methods.** Live testing a method buys three
  things and no more: that the session still authenticates, that the account is
  reachable, and that a real DC answers. Past that it re-tests code the offline
  suite already covers without a session. The live surface is deliberately tiny.
- **It does not prove the Passport login chain on real bytes.** The blob's nonce
  is already spent, so `PassportLoginService::login()` step (f) would correctly
  refuse the replay (`src/Teleframe/Passport/PassportLoginService.php:227-231`).
  This step stops at the decryptor on purpose.
- **It does not prove the nested §4 routines.** `decryptDataCredentials()` /
  `decryptFileCredentials()` take raw bytes inside a `values:Vector<SecureValue>`
  and nothing in `src/` invokes them; `SecureValueTraversal` only counts them.
- **It does not prove `help.getFile` document download.** There is no
  implementation at all.
- **It does not prove that Passport credentials can be *acquired*.** They cannot:
  no method pulls them. `help.getPassportConfig` is a country/language profile
  and `setPassportDataErrors` is a writer. The flow is user-initiated by
  construction, so this step is a **replay of bytes you captured**.
- **It does not answer whether the 23 layer-gap methods should be reachable.**
  `communities.*` and `ephemeral.*` do not exist at Layer 227 at all, so no live
  run can return "unimplemented method" for them. `messages.*` and
  `auth.*FirebasePnv*` do exist at 227 with different signatures, so for those a
  probe *would* be informative. Widening the wire or accepting the gap is a
  **product decision**, and `tests/Live/LiveLayerGapReportTest.php` reports the
  list without asserting an answer.
- **It does not prove 2FA.** `auth.signIn` on a 2FA account returns
  `auth.authorization` with `setup_password_required`; the SRP proof path is not
  exercised here.
- **It does not prove anything about your host app.** It exercises the package's
  engine only.

## 8. What the live surface is, and why it is small

A live test may dial a method only if it is on **one of two lists**:

1. `LiveEndpointPolicy::LIVE_SAFE` (`src/Teleframe/Warm/LiveEndpointPolicy.php:75`)
   — six entries: four pure reads (`users.getUsers`, `users.getFullUser`,
   `help.getConfig`, `help.getNearestDc`) plus the two 2FA-*state* reads
   (`auth.getPassword`, `account.getPassword`, which read and cannot begin a
   flow). The warm lane's fence.
2. `RunsLiveTelegram::liveFlowMethods()` (`tests/Concerns/RunsLiveTelegram.php`)
   — exactly **five** methods, each with a reason and a risk class
   (`quota` / `human` / `authorization`).

The two lists are proven **disjoint** by
`LiveGateContractTest::the_live_flow_carve_out_is_a_carve_out_and_not_a_widening()`,
which also pins that every entry carries a reason, that the account-destroying
methods (`account.deleteAccount`, `auth.resetAuthorizations`, `auth.logOut`) are
absent from both, and that the carve-out is still five. `LiveAuthFlowTest`
separately asserts the whole `auth.*` namespace resolves to exactly those five.

So a test added tomorrow cannot quietly dial `messages.sendMessage` by forgetting
to ask: `assertLivePolicyAllows()` refuses it by name and by reason.

## 9. A pre-existing test that dials Telegram in ordinary CI

Worth knowing, because it sits next to this lane:
`tests/Core/MTProto/ClientOfflineContractTest.php` contains
`test_live_mode_still_reaches_the_wire_for_a_valid_method`, which builds a
`live: true` client and calls `help.getNearestDc` — and which **skips if the
connection succeeds**. Its only assertion is that a connection *failure* is not a
schema refusal.

That is a real, narrow property, and it is left in place: it needs no account
and runs in every CI pass. It is **not** a substitute for this gate, because the
outcome it cares about (success) is the outcome it treats as a reason to stop.
`tests/Live/LiveWireGateTest.php` is the half it structurally cannot have: a run
that *requires* the connection to succeed and treats success as the assertion.

It also means a default `composer verify` already attempts a Telegram
connection from that one test.

## 10. Owner-gated — no agent can close these

- **A green run.** The gate is buildable; its green needs a funded account.
- **A dedicated bot for Passport.** The operator named this specifically.
- **The decision on the 23 layer-gap methods** — whether to widen the wire or
  accept the gap.
- **Browser-visible E2E** of `/tg` — no driver in the repository.