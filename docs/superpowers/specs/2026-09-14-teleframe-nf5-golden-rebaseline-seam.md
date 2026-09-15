CONTROLLER SURVIVAL: verbatim #4 (golden re-baseline seam, stored 2026-09-15)
Owner's operative verbatims: read #1 (mtproto-reverse-engineering), #2 (nf5-mirror-ingest),
#3 (clarification — "i dont want the auto generation any more", ban auto-gen forever) in
FULL after every compaction, then continue the goal: reverse-engineer MTProto Layer 227
surface (schema/sources/*.tl) into the correct CURATED NF5 MySQL surface — hand-authored
(never auto-generated) curated tf_* migrations + Eloquent models + relations, where as many
sub-agents as possible work in git worktrees on branches off the feature branch (controller
merges worktrees itself), then controller merges the full work to main itself and proves it
live against the owner's real MySQL + real seeded Telegram account. "The tables in mysql is
still the same shit" is the owner's core complaint — MySQL must ACTUALLY change.

REMAINING CONTROLLER-OWNED SURGERY (Task 0 close + Task 1-8 land + live proof):
1. tests/Schema/RegenerationGoldenTest.php lines 100/104/105/118/119/120 — surgical re-point
   the mirror-section count pins 37/400/400->0 and migration count 25->0? (mirror purged),
   and line 88/92 derefs must assert mirror ABSENT + curated tf_users present. Read-verify
   each exact string first, replace, re-run.
2. tests/Schema/ShipDialGoldenTest.php lines 46/47/61/83/113 — the bundled subset band must
   become app-owned curated-only: assertGreaterThanOrEqual(5, count) assertLessThanOrEqual(9)
   + stems create_tf_users/create_tf_chats/create_tf_messages + byte-identical to curated.
3. Merge feature branch -> main (controller-owned), run curated migrations against live MySQL
   with owner's seeded real account; ONLY then goal:complete with real proof.
Current truth: purge commit landed + pushed (f360e9bb + deletes); 4 domain worktrees + 3
merge(worktree) commits on feat branch VERIFIED; 3 RE skills installed (protocol-reverse-
engineering, ghidra-headless, performing-sqlite-database-forensics) at real paths; Schema
suite REAL run = Tests 14, Failures 8, Risky 1 — NOT green. Do not claim complete.
