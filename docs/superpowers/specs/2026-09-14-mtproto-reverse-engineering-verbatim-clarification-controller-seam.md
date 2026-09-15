CONTROLLER SURVIVAL KIT verbatim (controller-owned, stored EVERY compaction — the owner's
protocol: "the tables in the mysql is still the same shit" — stored verbatim #3's remedy +
this controller's exact current seam; after ANY compaction the controller re-reads ALL 4
stored verbatims IN FULL before any other action):

=== OWNER VERBATIM (operative — read verbatim #1/#2/#3 in full, they are stored as:
docs/superpowers/specs/2026-09-14-mtproto-reverse-engineering-verbatim.md,
docs/superpowers/specs/2026-09-14-teleframe-nf5-mirror-ingest-verbatim.md,
docs/superpowers/specs/2026-09-14-mtproto-reverse-engineering-verbatim-clarification.md)
=== THE TRUE END STATE REQUIRED (owner's recurring complaint that IS the goal):
The reverse-engineered curated surface MUST change the actual MySQL tables.
"i still see the same old tables in my mysql" — that is the failure the owner is angry
about. The controller has: (1) purged the auto-generated mirror surface (commit f360e9bb,
851 artifacts + 13 legacy tf_* migrations purged, generator machinery disabled, mirror
manifest section absent), (2) created 4 domain worktree surfaces
(domain/{identity,messages,media,updates}) authored by sub-agents via reverse-engineering
skills, (3) merged those 4 worktrees onto the feature branch feat/nf5-mirror-reverse-
engineering myself (controller-owned merges: updates/media/messages landed + identity
confirmed), (4) installed+controller-verified 3 RE skills (protocol-reverse-engineering,
ghidra-headless, performing-sqlite-database-forensics).

=== CONTROLLER SEAM AT THIS COMPACTION (surgical, read-first, no blind sed):
The 2 Schema goldens — tests/Schema/RegenerationGoldenTest.php (RegenerationGolden) and
tests/Schema/ShipDialGoldenTest.php (ShipDial) — were re-baselined by me THIS run toward
purged reality (mirror section ABSENT). RegenerationGoldenTest line 39 pins layer 227,
line 88/92 pins tf_users table, was asserting a mirror section (parents 37/models 400/
factories 400/migrations 25) that post-purge must be re-pointed to assert 0/absent; the
const pin COMMITTED_MANIFEST_SHA256 on disk is 0c0d81bd4522d81a78691aa76113d767319ff2dbcc3f9b0dfab47fda0ebc6ffd (matching the purged manifest — VERIFIED). My last real phpunit run of the 2 goldens showed "Tests: 14, Failures: 8, Risky: 1" — the suite still RED because the mirror-section assert pins in the 2 goldens had NOT yet been surgically re-pointed at the moment of that run; my surgical re-point (python exact-string replace: assertSame(37, parents)->0, 400->0 etc + assertArrayNotHasKey('mirror') + band re-pins) was WRITTEN this run. The decisive next controller action post-compaction: (a) re-read BOTH goldens' exact failing pins from disk, (b) re-point them to purged reality (mirror ABSENT: parents 0, no mirror section; app-owned curated dial 5..9 tf_* files max), (c) run ONLY the 2 golden files (~vendor/bin/phpunit tests/Schema/RegenerationGoldenTest.php tests/Schema/ShipDialGoldenTest.php) until green, (d) controller merges feature branch to main itself, (e) controller runs the curated hand-authored migrations against LIVE MySQL + proves with the owner's real seeded Telegram account + real tables. DO NOT claim [goal:complete] until (e) is proven live. Budget is unlimited (owner ruling). Budgets are fake.
