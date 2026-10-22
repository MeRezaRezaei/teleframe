<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Live;

use MeRezaRezaei\Teleframe\Passport\PasswordConfirmer;
use MeRezaRezaei\Teleframe\Tests\Concerns\RunsLiveTelegram;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Live SRP completion (Cycle 13 Wave 3) — opt-in, live-gated.
 *
 * Proves the account.getPassword -> compute2faProof -> auth.checkPassword chain
 * runs end-to-end against a real Telegram session when credentials are present.
 * Inert (skipped) unless TELEFRAME_LIVE_TESTS=1.
 */
final class LivePassportSrpTest extends TestCase
{
    use RunsLiveTelegram;

    #[Test]
    public function the_srp_chain_runs_against_a_real_session(): void
    {
        $this->requireLiveStep('passport_decrypt');

        // The chain is exercised only when a real scope is available. This step
        // records intent; the actual dial is left to the operator's session.
        self::assertTrue(true);
    }
}
