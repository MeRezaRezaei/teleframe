# Credential Vault Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** DB-encrypted `telegram_apps` + `telegram_accounts` vault with named resolver, so per-Laravel-user N apps/accounts work and live login stores sessions encrypted.

**Architecture:** Two package-owned migrations + two Eloquent models with `encrypted` casts + `Vault` singleton service bound in provider + `TeleframeClient::userFromVault/botFromVault` thin wrappers + 4 vault artisan commands + `--app/--account` on login. Env keeps only default account id; `TELEGRAM_*` stay as fallback.

**Tech Stack:** PHP 8.2+, Laravel Eloquent encrypted casts, sqlite :memory: tests, phpunit + phpstan level 5.

**Spec:** `docs/superpowers/specs/2026-09-09-credential-vault-design.md`

## Global Constraints

- Zero regex in `src/` outside `Laravel/Schema/Bot` (phpstan disallowedFunctionCalls).
- Never hand-edit generated artifacts (`schema/methods-*.json`, `generated/**`, `Methods/Generated/*`, skill md, `RpcErrorCatalog`).
- `.env` never committed; tests never require real credentials; live gates opt-in never CI.
- `composer verify` (phpunit + phpstan level 5 src-only) + `php bin/standalone-smoke.php` + `TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg` stay green.
- Redis wire keys opaque; public bind keys fixed; Layer 227 wire vs 229 schema gap intentional.

---

### Task 1: Migrations (apps + accounts tables)

**Files:**

- Create: `migrations/2026_09_09_000200_create_telegram_apps_table.php`
- Create: `migrations/2026_09_09_000201_create_telegram_accounts_table.php`
- Modify: `src/Schema/Generator/SchemaRegenerator.php:143` (APP_OWNED_MIGRATIONS)
- Test: `tests/Vault/TestCase.php`, `tests/Vault/VaultMigrationTest.php`

**Interfaces:**

- Consumes: `tl_user_bindings` migration shape (nullable morph, unique, no FK on morph).
- Produces: `telegram_apps`, `telegram_accounts` tables; ship-purge exemption.

- [ ] **Step 1: Write the failing test**

```php
public function test_vault_tables_exist(): void {
    self::assertTrue(Schema::hasTable('telegram_apps'));
    self::assertTrue(Schema::hasTable('telegram_accounts'));
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Vault/VaultMigrationTest.php`
Expected: FAIL table not found

- [ ] **Step 3: Write migrations** (copy bindings shape: id, nullable owner_type/owner_id, timestamps; apps add label unique + api_id int + api_hash text; accounts add app_id nullable FK nullOnDelete, label, type string, session/bot_token text nullable, dc_id int default 2, unique(app_id,label))
- [ ] **Step 4: Add both filenames to APP_OWNED_MIGRATIONS**
- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Vault/VaultMigrationTest.php`
Expected: OK

- [ ] **Step 6: Commit**

---

### Task 2: Models (encrypted casts + relations)

**Files:**

- Create: `src/Teleframe/Vault/TelegramApp.php`
- Create: `src/Teleframe/Vault/TelegramAccount.php`
- Test: `tests/Vault/VaultModelTest.php`

**Interfaces:**

- Consumes: Task 1 tables.
- Produces: `TelegramApp::accounts()`, `TelegramAccount::app()`, `account->credentials()` shape `['api_id','api_hash','session','bot_token','dc_id','type']`.

- [ ] **Step 1: Write failing test** (create app + user account, assert `api_hash` stored ciphertext != plaintext, relation resolves, `user` type requires app_id, `bot` http allows null app_id)
- [ ] **Step 2: Run to verify FAIL**
- [ ] **Step 3: Implement models** (`$casts api_hash/session/bot_token => encrypted`; validation via `saving` hook with string funcs only, no regex)
- [ ] **Step 4: Run to verify PASS**
- [ ] **Step 5: Commit**

---

### Task 3: Vault service + config default

**Files:**

- Create: `src/Teleframe/Vault/Vault.php`
- Create: `src/Teleframe/Vault/VaultConfig.php` (copy IdentityConfig pattern)
- Modify: `src/Laravel/config/teleframe.php` (add `vault.default_account`)
- Modify: `src/Laravel/Providers/TeleframeServiceProvider.php` (singleton Vault)
- Test: `tests/Vault/VaultResolverTest.php`

**Interfaces:**

- Consumes: Task 2 models.
- Produces: `Vault::account(label)`, `Vault::app(name)`, `Vault::defaultAccount()`; env `TELEFRAME_DEFAULT_ACCOUNT_ID`.

- [ ] **Step 1: Write failing test** (seed app+accounts, resolve by label exact-match, unknown label => null, default chain config->primary->env)
- [ ] **Step 2: Run FAIL**
- [ ] **Step 3: Implement Vault + VaultConfig + config key + singleton**
- [ ] **Step 4: Run PASS**
- [ ] **Step 5: Commit**

---

### Task 4: Client wrappers (userFromVault / botFromVault)

**Files:**

- Modify: `src/Laravel/Services/TeleframeClient.php` (add 2 methods + optional Vault injection)
- Test: `tests/Vault/VaultClientTest.php`

**Interfaces:**

- Consumes: Task 3 Vault.
- Produces: `userFromVault(label): UserAccountScope`, `botFromVault(label, transport='http'): BotClient|BotAccountScope`.

- [ ] **Step 1: Write failing test** (vault row -> scope built with row api_id/hash/session; bot http with token only)
- [ ] **Step 2: Run FAIL**
- [ ] **Step 3: Implement thin wrappers** (resolve credentials, delegate to existing `user()/bot()/botMtproto()`, never duplicate transport logic)
- [ ] **Step 4: Run PASS**
- [ ] **Step 5: Commit**

---

### Task 5: Artisan commands + login --app/--account

**Files:**

- Create: `src/Laravel/Console/VaultAddAppCommand.php` (`teleframe:vault:add-app`)
- Create: `src/Laravel/Console/VaultAddAccountCommand.php` (`teleframe:vault:add-account`)
- Create: `src/Laravel/Console/VaultListCommand.php` (`teleframe:vault:list`)
- Create: `src/Laravel/Console/VaultUseDefaultCommand.php` (`teleframe:vault:use-default`)
- Modify: `src/Laravel/Console/LoginCommand.php` (add `--app/--account`, store session encrypted)
- Modify: `src/Laravel/Providers/TeleframeServiceProvider.php:232` (register commands)
- Test: `tests/Vault/VaultCommandTest.php`

**Interfaces:**

- Consumes: Tasks 2-3.
- Produces: 4 commands + login flags; my.telegram.org manual-first text + DOMDocument best-effort fallback.

- [ ] **Step 1: Write failing test** (call add-app/add-account/list via Artisan, assert rows; login stores encrypted session)
- [ ] **Step 2: Run FAIL**
- [ ] **Step 3: Implement commands** (Laravel Prompts text/password like LoginCommand; manual steps printed; DOM fallback try/catch -> manual)
- [ ] **Step 4: Register in provider commands()**
- [ ] **Step 5: Run PASS**
- [ ] **Step 6: Commit**

---

### Task 6: Docs + gates + merge

**Files:**

- Modify: `docs/quickstart.md` (vault section), `.env.example` (TELEFRAME_DEFAULT_ACCOUNT_ID), `CHANGELOG.md`, `docs/superpowers/plans/2026-09-07-master-roadmap.md`
- Test: full gates

- [ ] **Step 1: Update docs + env example + changelog tick**
- [ ] **Step 2: Run `composer verify`** Expected: 1000+ tests OK, phpstan No errors
- [ ] **Step 3: Run `php bin/standalone-smoke.php`** Expected: exit 0
- [ ] **Step 4: Run `TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg`** Expected: OK
- [ ] **Step 5: Merge branch -> main, push, delete branch**
- [ ] **Step 6: Checkpoint goal with verification results**
