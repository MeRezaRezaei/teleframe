<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Store\ReclaimOutcome;
use MeRezaRezaei\Teleframe\Store\ReferencePolicy;
use PHPUnit\Framework\TestCase;

/**
 * ReferencePolicy + ReclaimOutcome (Cycle 16 W2, verbatim 2026-09-24:
 * "it helps keeping storage of laravel free").
 *
 * The policy is the safety interlock between "the hash row is the reference"
 * and "the local file may go". Its whole job is to be UNCONFIGURED-SAFE, so
 * most of these tests are about what must NOT happen: an unknown mode must not
 * quietly become the safe mode (that would hide a typo from an operator who
 * believes their disk is being reclaimed), and `drop-local-after-index` must
 * never be satisfied by an index row that cannot address a document.
 */
final class ReferencePolicyTest extends TestCase
{
    public function test_retain_local_is_the_default_and_never_reclaims(): void
    {
        self::assertFalse(ReferencePolicy::retainLocal()->reclaims());
        self::assertFalse(ReferencePolicy::retainLocal()->permitsReclaim(true));
        self::assertSame(ReferencePolicy::RETAIN_LOCAL, ReferencePolicy::retainLocal()->mode());
    }

    public function test_from_string_defaults_to_the_safe_policy(): void
    {
        self::assertSame(ReferencePolicy::RETAIN_LOCAL, ReferencePolicy::fromString(null)->mode());
        self::assertSame(ReferencePolicy::RETAIN_LOCAL, ReferencePolicy::fromString('')->mode());
        self::assertSame(ReferencePolicy::RETAIN_LOCAL, ReferencePolicy::fromString('  ')->mode());
    }

    public function test_from_string_normalizes_case_and_padding(): void
    {
        self::assertSame(
            ReferencePolicy::DROP_LOCAL_AFTER_INDEX,
            ReferencePolicy::fromString('  Drop-Local-After-Index ')->mode(),
        );
    }

    public function test_drop_local_requires_the_index_proof_not_just_the_choice(): void
    {
        $policy = ReferencePolicy::dropLocalAfterIndex();

        self::assertTrue($policy->reclaims());
        // The operator's standing choice is not the per-call proof.
        self::assertFalse($policy->permitsReclaim(false));
        self::assertTrue($policy->permitsReclaim(true));
    }

    public function test_unknown_mode_is_loud_rather_than_silently_safe(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown reference policy "delete-everything"');

        ReferencePolicy::of('delete-everything');
    }

    public function test_outcome_states_are_distinguishable(): void
    {
        self::assertTrue((new ReclaimOutcome(ReclaimOutcome::RETAINED))->isRetained());
        self::assertTrue((new ReclaimOutcome(ReclaimOutcome::RECLAIMED))->isReclaimed());
        self::assertTrue((new ReclaimOutcome(ReclaimOutcome::REFUSED))->isRefused());
    }

    public function test_only_a_real_deletion_counts_as_freed_bytes(): void
    {
        // A dry run decides WITHOUT deleting, so it must never claim it freed
        // anything — that is how an operator gets told their disk is clear when
        // it is not.
        self::assertFalse((new ReclaimOutcome(ReclaimOutcome::REFUSED, ReclaimOutcome::REASON_DRY_RUN))->freedBytes());
        self::assertFalse((new ReclaimOutcome(ReclaimOutcome::RECLAIMED, ReclaimOutcome::REASON_ALREADY_GONE))->freedBytes());
        self::assertTrue((new ReclaimOutcome(ReclaimOutcome::RECLAIMED, ReclaimOutcome::REASON_DELETED))->freedBytes());
    }

    public function test_reclaim_outcome_rejects_an_unknown_state(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ReclaimOutcome('obliterated');
    }

    public function test_policy_builds_an_outcome_without_touching_a_disk(): void
    {
        $outcome = ReferencePolicy::retainLocal()->result(ReclaimOutcome::RETAINED, ReclaimOutcome::REASON_POLICY_RETAINS);

        self::assertSame(ReclaimOutcome::RETAINED, $outcome->state());
        self::assertSame(ReclaimOutcome::REASON_POLICY_RETAINS, $outcome->reason());
    }
}
