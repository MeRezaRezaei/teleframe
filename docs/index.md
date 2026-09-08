# Teleproto Docs

Telegram power for Laravel apps — Bot HTTP API, native MTProto 2.0 for users *and* bots, Mini App auth, Passport KYC, and streaming uploads from your Storage disks. `.env`-driven, stateless sessions, no daemon.

> **Consumer layer merged in Phase 2.** The separate `teleclient` package was
> archived and its capabilities absorbed into this repo as modules under
> `MeRezaRezaei\Teleframe\*`: tenant-scoped Ingest into Postgres, a Redis stream
> Bus + consumer + daemon + quota-aware backfill, and an encrypted
> Telegram-channel Backup vault. See [ingest](ingest.md), [bus](bus.md),
> [backup](backup.md) below.

## Start here

| Doc | What's inside |
| :--- | :--- |
| [Quickstart](quickstart.md) | 5 copy-paste recipes: bot message, user login wizard + MTProto, Mini App route guard, Passport decryption, Storage → MTProto streaming upload |
| [Bot API Client](bot-client.md) | Bot over HTTP: tokens, keyboards, webhooks & polling, generic `call()`, native MTProto bot mode |
| [User MTProto Client](user-client.md) | User accounts over MTProto 2.0: API credentials, login/2FA, session strings, calls from stored sessions |
| [Telegram Passport](telegram-passport.md) | End-to-end encrypted KYC: RSA keypair setup, decrypting Passport credentials in a webhook |
| [Scaling](scaling.md) | One process per account (Horizon), cold-start numbers (49 ms cold / 5 ms warm), backpressure contract, honest load limits & roadmap |
| [Ingest](ingest.md) | Tenant-scoped raw TL → Postgres truth: updates / method responses, route dedup, idempotency, events (`MeRezaRezaei\Teleframe\Ingest`, public face `Teleclient`) |
| [Bus](bus.md) | Redis Streams fan-in, hot-reload routing, `teleframe:ingest` consumer, multi-account `Daemon`, quota-aware `teleframe:backfill`, systemd unit |
| [Backup](backup.md) | Encrypted Telegram-channel backup vault: chunking, Argon2id + XChaCha20-Poly1305, manifest-first restore, verify, `teleframe:backup` |

## For AI agents

- [../skills/](../skills/) — per-method reference files for the curated MTProto/Bot-API method catalog (schema-accurate, machine-readable)

## Changelog

- [../CHANGELOG.md](../CHANGELOG.md) — release history
