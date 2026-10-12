# Usage — the six release areas

> **Release areas (cycle 10).** The consumer-facing surfaces of Teleframe,
> each one documented against the shipped source — every class, facade and
> console command named below exists and carries the stated signature in
> `src/`. When docs and code disagree, **code wins**: re-verify against
> `src/` before relying on a call.

Teleframe is ONE package with two layers:

| Layer | Namespace | What it is |
| --- | --- | --- |
| **Engine** | `MeRezaRezaei\Teleframe\Core\` | The MTProto 2.0 wire (`Client`, `EncryptedConnection`, `SessionData`, crypto, TL codec), the schema + method builders (`Methods`, `InputPeer`, keyboards), and `PassportDecryptor`. Pure PHP, no Laravel required. |
| **Consumer layer** | `MeRezaRezaei\Teleframe\{Notify,Store,Backup,Vault,Handler,Message,…}` + `MeRezaRezaei\Teleframe\Laravel\` | Tenant-scoped Ingest/Bus/Daemon, the notify core, the TStore archive + encrypted backup vault, the handler substrate, and the Laravel glue (facades, `teleframe:*` commands, providers, config). |

There are **two different `Teleframe` classes** — don't mix them up:

- `MeRezaRezaei\Teleframe\Teleframe` — the compose-not-own **facade class**: `app(Teleframe::class)->on()/onMessage()/observe()/ingest()/send()/store()/storeBackup()/backup()/app()/passport()/…`.
- `MeRezaRezaei\Teleframe\Laravel\Facades\Teleframe` (alias `TF`) — the Laravel **facade over `TeleframeClient`**: `Teleframe::user()/fromSession()/forAccount()/bot()/botMtproto()/userFromVault()/botFromVault()/callMany()/dispatch()/schemaLayer()`.

---

## The six areas (install first)

| Doc | Area | What you'll find |
| :--- | --- | --- |
| [host-install.md](host-install.md) | **INSTALL** | **Start here if you are integrating the package into another project.** The numbered integrator-friction list, the six facades' unconfigured contract, and the gate (`bin/prove-host-install.php`) that proves the package installs and boots in a fresh host app. |
| [notify.md](notify.md) | **NOTIFY** | Acquiring clients (`user`/`fromSession`/`bot`/`botMtproto`), sending via `BotClient` and the `Methods` builders, `ReplyKeyboard`/`InlineKeyboard`, the `Notify` facade (`log`/`to`/`report`), `PendingNotify`, `teleframe:notify`, `MessageFactory` template plans. |
| [store-backup.md](store-backup.md) | **STORE + BACKUP** | The TStore encrypted object archive (`teleframe:tstore`, sealed/raw, `TStore` facade), the Telegram-channel backup vault (`teleframe:backup`), the credential vault (`teleframe:vault-*`), and the DB store-backup (`teleframe:tstore backup|schedule`, `StoreBackup` facade). |
| [mtproto.md](mtproto.md) | **RAW MTProto** | The raw wire: `SessionData` (export/import), `Client`, `EncryptedConnection` (`call`/`callBatch`, Layer 227), TL constructor arrays, `InputPeer::*`, `Methods` builders, `PasswordCalculator` (2FA SRP). |
| [app-framework.md](app-framework.md) | **APP FRAMEWORK** | The handler substrate: `HandlerRegistry` (`on`/`onMessage`/`observe`), zero-regex matcher, `Pipeline` onion, echo elimination + replay dedup, `UpdateDispatcher`, `Update`, `Teleframe::run()` fixtures. Coordinates with the in-depth [handlers.md](../handlers.md). |
| [passport.md](passport.md) | **PASSPORT** | Telegram Passport KYC: `EncryptedCredentials` intake, `PassportDecryptor` (decryption steps + one-message oracle hardening), the `PassportLoginService` login orchestrator, nonce issuance, `teleframe:passport`, `TELEFRAME_PASSPORT_*` config. |

## Quick orientation

- **Every Laravel entry point is a facade**: `Teleframe` / `TF` (client),
  `Notify`, `TStore`, `StoreBackup`, `Passport`, `TelegramApp`. Each resolves
  a container seam (`teleclient.*.*`) that a host may rebind; unconfigured
  seams stay **constructible and loud-on-use** — they throw on first real
  use, never silently drop.
- **Every console surface is one class per file** under
  `src/Laravel/Console/` (or `src/Teleframe/Notify/NotifyCommand.php`).
- **Never commit session strings**: exported sessions are credentials.
- The **wire speaks Layer 227** while the **method catalog is Layer 229** —
  an intentional gap, never "fix" it.