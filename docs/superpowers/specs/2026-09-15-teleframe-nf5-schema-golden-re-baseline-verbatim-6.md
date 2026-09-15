# CONTROLLER SURVIVAL KIT — verbatim #6 (Schema-golden re-baseline seam; overrides nothing, sits alongside #1..#5 in docs/superpowers/specs/; re-read ALL SIX IN FULL after every compaction, this is the owner's non-negotiable protocol to survive the 200k context limit)

## Owner's operative truth (re-derived THIS window from the disk sit, verbatim against the verbatims)
- **The 2 goldens are STILL RED.** Last real run: `Tests: 14, Assertions: 34, Failures: 8, Risky: 1`. NOT green, NOT complete, controller says so plainly.
- Committed manifest sha pin on disk (`0c0d81bd4522d81a78691aa76113d767319ff2dbcc3f9b0dfab47fda0ebc6ffd`) NOW EQUALS the disk manifest sha — that seam is genuinely green-ready.
- ShipDialGoldenTest already re-baselined to purged reality (assertGreaterThanOrEqual(5)/assertLessThanOrEqual(9) at lines 46/47 + curated stems tf_users/tf_chats/tf_messages at line 53).
- RegenerationGoldenTest still derefs a `mirror` section that is (correctly, post-purge) ABSENT — the assertArrayHasKey('tf_users')/parents/models/factories pins leak the pre-purge mirror-present counts.
- **MySQL NEVER CHANGED.** That is the owner's gate: "the tables in mysql is still the same shit". Live proof + real seeded account NOT done. The end-state that defines complete.

## The controller's surgical next move (to be executed on the far side, read-first)
1. Re-read ALL stored verbatims in full (this one + #1..#5).
2. Read BOTH goldens' exact current failing pins from disk (no sed from memory).
3. Surgically re-point RegenerationGolden's mirror-section derefs to purged reality (mirror ABSENT — assertArrayNotHasKey('mirror'); no mirror deref).
4. Run only the 2 goldens until green.
5. Merge feature branch to main yourself; merge curated migrations to live MySQL + real seeded account; prove tables ACTUALLY change.
