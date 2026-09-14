# Teleframe — MTProto Reverse-Engineering Directive (Owner's Verbatim, 2026-09-14, 3rd verbatim)

> **Record type:** Raw verbatim, 2026-09-14. The owner's words below are stored
> **exactly as written** — no spelling corrections, no rewording. Section
> headings add navigation only; they are not part of the original words.
> This document is a clarification of the 2026-09-14 NF5 mirror + ingest
> verbatim (docs/superpowers/specs/2026-09-14-teleframe-nf5-mirror-ingest-verbatim.md)
> and an upstream source of truth for all schema/migration/model decisions.
> After every context compaction, READ THIS FILE AGAIN before continuing —
> it is the key to not losing the goal across compactions (200k context limit).
> When a plan conflicts with this, the plan is wrong.

---

## The exact words (verbatim, uncorrected)

ok listen i have a simple yet significtnly imporntant thing to say i actaully intended to reverse enginering the telegram mt proto into a database with nf 5 but what we have now is the generated migrations that includes all routes of the mt proto its obviously god damn wrong and the only fix is you fire sub agents just like what i told you in my today verbatim and you yourself generate the migrations not auto generated you must read the mtproto generate the migrations and purge the current ones so you can gradugaly genrate the full correct migration and models and realtions of the models as expected so i dont want the auto generation any more and this work is god domn big you must first store my verbatim here too along side the first one and reaturn to it until the end and use as much as its possible sub agents in another branch from main and git work tree then ask them to cover the reserver enginering then merger the worktrees your self and then merge the full work to main now that you have the other laravel installed the mysql working and my telegram acocunt ready to see in real live world you should be able to make it without any flaw during this you must use palning skill the executing paln skill and the sub agent driven so the work wil be managed and i expect finally to have the migariton mirror reversed enginered since later on i am going to say how to make the index inside telgram and it requires the exact models and migration to be there and this message is a clarification of the first verbatim so the rest of how it should work is on your hand and by making it you should be able to continue the first veratim and make it work in real world too so store the new verbatim and do it set the goal and do not forget after each compaction you should again read the verbatims to let you not lsot goal across compaction its the key to this since your context is limited to 200 k and its not letting us keep things straght so this is how we are going to solve this problem

---

## Operational summary of the directive (navigation aid, not part of the words)

1. **Reverse-engineer the Telegram MTProto into an NF5 database** — the hand-derived
   migration mirror, models, and relations — NOT the current auto-generated mirror.
2. **Purge the current auto-generated migrations**; then gradually generate the full
   correct migration + models + relations as expected.
3. **No more auto-generation** for the shipment: read the MTProto (schema/sources/*.tl,
   Layer 229 catalog) and derive the migrations by hand.
4. **Sub-agents**: use them as much as possible, on a branch from `main`, via git
   worktrees; ask them to cover the reverse engineering. Merge the worktrees yourself,
   then merge the full work to main.
5. **Environment is ready**: other Laravel installed (teleframe-app host), MySQL
   working, the real Telegram account seeded — so the result must be verifiable in
   the live world (real data → fully nf5 relational DB; any failing FK is a clue to
   a wrong ingest path, per verbatim #1).
6. **Skills required**: planning skill, executing-plans skill, subagent-driven
   development — so the work is managed.
7. **Definition of done**: the migration mirror reverse-engineered, with exact models
   and migrations present — because the owner will later specify how to make the
   index inside Telegram, which requires the exact models and migrations.
8. **Compaction protocol**: set the goal; after each compaction re-read the verbatims
   to keep the goal across the 200k context limit.