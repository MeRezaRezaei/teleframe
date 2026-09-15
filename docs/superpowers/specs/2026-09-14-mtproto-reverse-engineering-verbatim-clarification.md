# Teleframe NF5 — Owner Verbatim #3 (reverse-engineering clarification; stored ALONGSIDE verbatim #1 per owner's explicit compaction-survival protocol)

> **Quantum survival record.** This is the owner's verbatim #3, stored exactly word-for-word below. It is a CLARIFICATION of verbatim #1 (the original reverse-engineering directive). After EVERY context compaction, the controller MUST re-read BOTH this file AND its sibling verbatim #1 (`2026-09-14-mtproto-reverse-engineering-verbatim.md`) — and the NF5 mirror-ingest verbatim (verbatim #2) — before continuing. This is the owner's explicit mechanism to survive the 200k context limit across compactions. Re-reading the verbatims after every compaction is non-negotiable ("do not forget after each compaction you should again read the verbatims to let you not lost goal across compaction its the key to this since your context is limited to 200 k and its not letting us keep things straght so this is how we are going to solve this problem").

---

ok listen i have a simple yet significantly important thing to say i actually intended to reverse engineering the telegram mt proto into a database with nf 5 but what we have now is the generated migrations that includes all routes of the mt proto its obviously god damn wrong and the only fix is you fire sub agents just like what i told you in my today verbatim and you yourself generate the migrations not auto generated you must read the mtproto generate the migrations and purge the current ones so you can gradually generate the full correct migration and models and relations of the models as expected so i dont want the auto generation any more and this work is god dam big you must first store my verbatim here too along side the first one and return to it until the end and use as much as its possible sub agents in another branch from main and git worktree then ask them to cover the reverse engineering then merger the worktrees your self and then merge the full work to main now that you have the other laravel installed the mysql working and my telegram account ready to see in real live world you should be able to make it without any flaw during this you must use planning skill the executing plan skill and the sub agent driven so the work will be managed and i expect finally to have the migration mirror reverse engineering since later on i am going to say how to make the index inside telegram and it requires the exact models and migrations to be there and this message is a clarification of the first verbatim so the rest of how it should work is on your hand and by making it you should be able to continue the first verbatim and make it work in real world too store the new verbatim here too along side the first one and reaturn to it until the end and do not forget after each compaction you should again read the verbatims to let you not lost goal across compaction its the key to this since your context is limited to 200 k and its not letting us keep things straght so this is how we are going to solve this problem

---

## Controller's operative summary of this verbatim (the durable goal, re-derived from the words above — do not lose across compaction)

1. **Purge the current auto-generated mirror** from the shipped tree — done (Task 0 purge, commit purged 851 generated artifacts + 13 legacy `tf_*` migrations, mirror stage removed from SchemaRegenerator; branch `feat/nf5-mirror-reverse-engineering`, tip `f360e9bb` p360e9bb... actually f360e9bb, pushed).
2. **You yourself generate the migrations by hand** — reading the MTProto source (`docs/superpowers/specs` sources: `schema/sources/TL_telegram_v227.tl` etc.), derived NF5-correct migrations, models, and model relations — no auto-generation, no regenerator, no "generated-by" banners.
3. **Use as many sub-agents as possible**, dispatched in another branch off `main` via `git worktree` (controller-created worktrees `teleframe-{identity,messages,media,updates}` on `domain/*` branches, topology confirmed), each covering a reverse-engineering domain. Sub-agents have returned their domain surfaces (identity, messages, media, updates). I (controller) MERGE the worktrees myself, then merge the full work to `main`.
4. **Verify against the real world last**: Laravel host installed, MySQL working, seeded real Telegram account ready — the shipped surface must hold up in live NF5 reality.
5. **Compaction survival (mandatory):** after EVERY compaction re-read the verbatims (this one + verbatim #1 + verbatim #2) so the goal is never lost across the 200k limit.

## Ledger (where the work actually stands — honest, no fabricated green)

- **Task 0 (purge) — LANDED + PUSHED.** Commit `f360e9bb` (feat/nf5-mirror-reverse-engineering): `git rm` 851 generated mirror artifacts + 13 legacy `tf_*` migrations; `SchemaRegenerator::mirror()` stage removed; generator-machinery (`MirrorCatalog`, `MirrorTableResolver`, `MirrorMigrationWriter`, models/DTM writers, generators command, `src/Schema/Generator/*`) deleted with `git rm`; tests that pinned the mirror surface re-baselined/removed. Ledger ruling (sealed earlier): extension surface stays BANNED (owner: "i dont want the auto generation any more"); decompose/writer write-path classes kept and re-pointed at hand-authored curated dial surface (Tasks 2-8) — ShipDial in `ShipDialGoldenTest` now asserts the purged subset (app-owned only, no `tf_*` mirror), regeneration golden re-baselined to the mirror-absent manifest.
- **Domain sub-agents (Tasks 2-9) — dispatched in 4 worktrees, each returned a domain surface.** Worktree list (controller), each on a `domain/*` branch at base f360e9bb:
  - `teleframe-identity` → domain/identity
  - `teleframe-messages` → domain/messages
  - `teleframe-media` → domain/media
  - `teleframe-updates` → domain/updates
  Each returned a DONE report (reported summary pending full composer verify ledgering).
- **Controller next move (me, not yet executed):** create/verify each domain worktree's component surface green (Schema suite: golden re-baseline independent; Ingest write-path suite expected-red, ledgered, until Tasks 2-8 restore the hand-authored dial tables) → merge all 4 worktrees myself onto the feature branch → re-baseline remaining Schema goldens to purged reality → `composer verify` full green → merge feature to `main` → live NF5 verification with the real seeded account + MySQL.

## Survival kit (files to re-read; also mirrored in plan `docs/superpowers/plans/2026-09-14-mtproto-reverse-engineering.md`)

- docs/superpowers/specs/2026-09-14-mtproto-reverse-engineering-verbatim.md (verbatim #1)
- docs/superpowers/specs/2026-09-14-mtproto-reverse-engineering-verbatim-clarification.md (THIS file — verbatim #3)
- docs/superpowers/specs/2026-09-14-teleframe-nf5-mirror-ingest-verbatim.md (verbatim #2)
- docs/superpowers/plans/2026-09-14-mtproto-reverse-engineering.md (the NF5 plan: Tasks 0-9)
