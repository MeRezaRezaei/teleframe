<div align="center">

# Teleframe ⚡

**Telegram power with almost zero friction — for PHP & Laravel.**

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%208.2-8892BF.svg?style=flat-square)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012-FF2D20.svg?style=flat-square)](https://laravel.com/)

</div>

One package for the whole Telegram surface: native MTProto 2.0 for users *and*
bots, the Bot HTTP API, Mini App auth, Passport KYC, and — merged in as
modules — the consumer layer (tenant-scoped Ingest, Redis stream Bus + daemon +
backfill, encrypted backup vault) plus a one-class handler facade.

```bash
composer require merezarezaei/teleframe
```

## One class composes everything

```php
use MeRezaRezaei\Teleframe\Teleframe;

$teleframe = app(Teleframe::class);

$teleframe->onMessage(function ($update) use ($teleframe) {
    // every stored update reaches you through one pipeline
    $teleframe->send($update->accountId, ['random_id' => random_int(1, PHP_INT_MAX), 'text' => 'got it']);
});
```

- `Teleframe` — compose-not-own facade: `ingest`, `ingestResponse`,
  `onMessage` / `on` (route by constructor, zero-regex), `run` (fixtures
  through the REAL pipeline), `user`/`chat`/`channel`, `route`, `backup`,
  `schemaLayer`, `send` (writes the Q2d echo-elimination registry).
- `Teleclient` — the ingest-only public face, retained byte-stable.

## What's inside

| Area | Namespace / docs | What it is |
|---|---|---|
| MTProto engine | `MeRezaRezaei\Teleframe\Core\` | Raw binary wire: transport, crypto (DH + 2FA SRP), TL codec (zero-regex), method registry, typed RPC exceptions, Layer 227 wire |
| Bot HTTP API | `MeRezaRezaei\Teleframe\Bot\` | `BotClient`, scopes, fluent builders, webhooks & polling |
| Schema pipeline | `MeRezaRezaei\Teleframe\Schema\` + `generated/` | `.tl` → models/`Methods` builders, `teleframe:schema-update` (diff + regenerate + stamp, no migrate), Layer 229 catalog |
| Laravel glue | `MeRezaRezaei\Teleframe\Laravel\` | `TeleframeServiceProvider`, `teleframe:*` commands, webhook route macro, Mini App middleware, facades |
| Consumer modules | `MeRezaRezaei\Teleframe\{Ingest,Bus,Daemon,Backfill,Backup}\` | [ingest.md](docs/ingest.md) · [bus.md](docs/bus.md) · [backup.md](docs/backup.md) |
| Handlers | `MeRezaRezaei\Teleframe\Handler\` | [handlers.md](docs/handlers.md) — registry + 26-line onion + echo elimination + `Testing\FakeDispatcher` |

Docs: [quickstart.md](docs/quickstart.md) · [index.md](docs/index.md) ·
`php artisan teleframe:doctor`.

## Influence & credit

Teleframe draws heavily on the Nutgram-style handler ergonomics and follows the
owner's unification design (see `docs/superpowers/specs/`). The original
`teleclient` package that held the consumer layer is archived.

MIT licensed.