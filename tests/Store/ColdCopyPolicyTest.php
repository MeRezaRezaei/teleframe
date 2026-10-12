<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Store\ReferencePolicy;
use PHPUnit\Framework\TestCase;

/**
 * The cold posture as a VALUE (V1.2, 2026-09-27), including the ruling that
 * the shipped default does not change.
 *
 * `test_the_shipped_default_still_frees_nothing_and_trusts_nothing` is the
 * decision this file exists for. The V1.2 deduction was that the default
 * posture frees nothing, and the obvious "fix" — flipping the default so the
 * goal reads 100 — would be a package that deletes a host's local files on
 * install with nobody having asked. So the default is pinned here in an
 * assertion, the cold behaviour is a NAMED mode instead, and if a future cycle
 * ever flips the default this test fails and the change has to be argued for in
 * the open rather than slipped in.
 */
final class ColdCopyPolicyTest extends TestCase
{
    public function test_the_shipped_default_still_frees_nothing_and_trusts_nothing(): void
    {
        foreach ([null, '', '   '] as $configured) {
            $policy = ReferencePolicy::fromString($configured);

            self::assertSame(ReferencePolicy::RETAIN_LOCAL, $policy->mode());
            self::assertFalse($policy->reclaims(), 'the default must not free a local file');
            self::assertFalse($policy->trustsRemote(), 'the default must not treat a missing local row as licence to go remote');
            self::assertSame(ReferencePolicy::PROOF_LOCAL_ROW, $policy->durabilityProof());
        }
    }

    public function test_the_cold_posture_is_named_and_parsed_like_any_other_mode(): void
    {
        $policy = ReferencePolicy::fromString('  Cold-Remote-Authoritative ');

        self::assertSame(ReferencePolicy::COLD_REMOTE_AUTHORITATIVE, $policy->mode());
        self::assertTrue($policy->reclaims(), 'the cold posture is a RECLAIMING posture: that is the point of the name');
        self::assertTrue($policy->trustsRemote());
        self::assertSame(ReferencePolicy::PROOF_REMOTE_DOCUMENT, $policy->durabilityProof());
    }

    public function test_trusting_the_remote_is_not_implied_by_dropping_local_files(): void
    {
        $policy = ReferencePolicy::dropLocalAfterIndex();

        self::assertTrue($policy->reclaims());
        self::assertFalse($policy->trustsRemote(), 'drop-local-after-index still demands a LOCAL row as the proof');
        self::assertSame(ReferencePolicy::PROOF_LOCAL_ROW, $policy->durabilityProof());
    }

    public function test_the_per_call_proof_is_still_separate_from_the_posture(): void
    {
        $cold = ReferencePolicy::coldRemoteAuthoritative();

        self::assertTrue($cold->permitsReclaim(true));
        self::assertFalse($cold->permitsReclaim(false), 'the operator\'s standing choice is not the per-call proof');
    }

    public function test_a_typo_in_the_cold_mode_is_loud_rather_than_silently_safe(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown reference policy "cold-remote"');

        ReferencePolicy::fromString('cold-remote');
    }
}
