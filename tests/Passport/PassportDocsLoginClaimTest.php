<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * CLAIM 3, still a docs-consistency assertion, and deliberately so.
 *
 * The defect this file was born for WAS a doc contradicting code:
 * `docs/telegram-passport.md` said the engine "writes the binding and logs the
 * user in" and five lines later said the package never calls `Auth::login`.
 * Both cannot be true, and the honest position at the time was that all three
 * routes sat inside the `auth` group and the callback refused 401 without a
 * session, so the flow ATTACHED and authenticated nobody.
 *
 * That position is now only HALF true, and a docs test that pinned it whole
 * would now be pinning a lie of its own: the package ships a second route pair
 * that does start with no session and end with one. So the test has moved with
 * the code, in the only direction that keeps its purpose — the docs must
 * describe the flow that EXISTS, and the two claims that are easy to get wrong
 * in opposite directions are each pinned:
 *
 *   * the ATTACH flow still requires an already-authenticated user (the old
 *     false-claim guard, unchanged in force), and
 *   * the LOGIN flow is a SEPARATE capability that is OFF by default, bound to
 *     a browser session, and never provisions an account — a doc that now
 *     oversold the login would be the same class of defect as the one this
 *     file was written for.
 *
 * Every code claim below is asserted against the source, so a doc that
 * describes a route or a guard that no longer exists fails here.
 */
final class PassportDocsLoginClaimTest extends TestCase
{
    private function doc(string $relative): string
    {
        $path = dirname(__DIR__, 2).'/'.$relative;
        self::assertFileExists($path);

        return (string) file_get_contents($path);
    }

    private function source(string $relative): string
    {
        $path = dirname(__DIR__, 2).'/'.$relative;
        self::assertFileExists($path);

        return (string) file_get_contents($path);
    }

    // ── the ATTACH half: the old guard, still in force ────────────────

    /**
     * The false claim must not come back. Matched as PHRASES rather than the
     * word "login", because an honest guide has to be able to DISCUSS login —
     * to say what a host must build, and now also to describe the shipped
     * login flow. What the attach section may not do is claim the ATTACH flow
     * itself performs a login.
     */
    public function test_the_guide_does_not_claim_the_attach_flow_performs_a_login(): void
    {
        $text = $this->doc('docs/telegram-passport.md');

        // The attach section is bounded by its own heading, so this cannot be
        // satisfied by the login section further down.
        $attach = substr($text, 0, (int) strpos($text, '### 3.3'));

        foreach ([
            'writes the binding and logs the user in',
            'and logs the user in',
            'logs the user in',
            'log in the user',
            'logs them in',
        ] as $claim) {
            self::assertStringNotContainsString(
                $claim,
                $attach,
                "the attach section still claims the flow performs the login (\"{$claim}\")",
            );
        }
    }

    /** The positive half: the user is already authenticated, and it attaches. */
    public function test_the_guide_states_the_attach_not_login_position(): void
    {
        $text = $this->doc('docs/telegram-passport.md');

        self::assertStringContainsString(
            'already',
            $text,
            'the guide must say the user is ALREADY authenticated',
        );
        self::assertStringContainsString(
            '401',
            $text,
            'the guide must say the attach callback refuses 401 without a bound session',
        );
    }

    /**
     * The usage doc's honest precondition is about `PassportLoginService`, and
     * it is still true. Pin it so a future edit cannot quietly turn the usage
     * doc into a promise that the ATTACH path keeps a session user id.
     */
    public function test_the_usage_doc_keeps_its_honest_session_precondition(): void
    {
        $text = $this->doc('docs/usage/passport.md');

        self::assertStringContainsString('Auth::id()', $text);
        self::assertStringContainsString('authenticated session', $text);
    }

    // ── the LOGIN half: the new truth, pinned ─────────────────────────

    /**
     * The guide must NAME the login flow and its switch, or a reader who came
     * for "log users in with Telegram" finds only the attach flow and walks
     * away — which is the exact gap that scored the shipped surface 70/100.
     */
    public function test_the_guide_names_the_login_flow_and_its_switch(): void
    {
        $text = $this->doc('docs/telegram-passport.md');

        self::assertStringContainsString('passport/login/start', $text);
        self::assertStringContainsString('passport/login/callback', $text);
        self::assertStringContainsString('anonymous_login', $text);
    }

    /**
     * The oversell guard, and the one that matters most. A guide that says the
     * package can log a user in without saying the capability is OFF by
     * default, bound to a session, and never provisions, is the same defect as
     * the original: a promise the reader will build on.
     */
    public function test_the_guide_states_what_the_login_flow_will_not_do(): void
    {
        $text = $this->doc('docs/telegram-passport.md');

        self::assertStringContainsString('never creates an account', $text);
        self::assertStringContainsString('never leaks a credential', $text);
        self::assertStringContainsString('No live session is ever replaced', $text);
        self::assertStringContainsString('refuse', $text);
    }

    /** The same three commitments, in the config table a reader actually reads. */
    public function test_the_usage_doc_documents_every_anonymous_login_key(): void
    {
        $text = $this->doc('docs/usage/passport.md');

        foreach (['anonymous_login', 'unknown_user', 'login_rate_limit'] as $key) {
            self::assertStringContainsString(
                $key,
                $text,
                "docs/usage/passport.md does not document the {$key} config key",
            );
        }

        self::assertStringContainsString('OFF by default', $text);
        self::assertStringContainsString('never creates a row', $text);
    }

    // ── the docs are grounded in the code they describe ───────────────

    /**
     * A doc is a claim about code, so the claims are checked against the code.
     * Each of these is a sentence the guide makes; if the implementation moves,
     * the guide is now wrong and this fails rather than the reader finding out.
     */
    public function test_the_claims_the_guide_makes_about_the_login_flow_still_hold(): void
    {
        $routes = $this->source('src/Laravel/Http/Controllers/Dashboard/DashboardRoutes.php');

        // "registered in their OWN group … without your auth guard".
        self::assertStringContainsString('registerAnonymousPassportRoutes', $routes);
        self::assertStringContainsString("'web'", $routes);
        self::assertStringContainsString('throttle:', $routes);
        self::assertStringNotContainsString(
            "'auth'",
            substr($routes, (int) strpos($routes, 'private static function registerAnonymousPassportRoutes')),
            'the anonymous group must not carry the host auth guard',
        );

        $service = $this->source('src/Teleframe/Passport/PassportAnonymousLoginService.php');

        // "a non-nullable string $sessionFingerprint with no default".
        self::assertStringContainsString(
            'string $flowToken, string $sessionFingerprint, int $authenticatedUserId',
            $service,
            'the fingerprint must stay a non-nullable, non-defaulted parameter',
        );

        // "bound to a fingerprint … the check is not a branch a caller can skip".
        self::assertStringContainsString(
            'SessionFingerprint::matches($binding->sessionFingerprint(), $sessionFingerprint)',
            $service,
            'the unconditional session comparison must remain',
        );

        // "it never creates an account … any other value is named back to you".
        self::assertStringContainsString('unknownUserPolicy()', $service);

        $config = $this->source('src/Teleframe/Passport/PassportConfig.php');
        self::assertStringContainsString("'anonymous_login' => false", $config, 'the login capability must default OFF');
        self::assertStringContainsString("'unknown_user' => 'refuse'", $config, 'the policy must default to the safe value');
    }

    /**
     * The 401 refusal is what makes "cannot replace a live session" a fact
     * about the code rather than a claim about the doc, and the attach flow's
     * own 401 is the premise the whole attach section rests on. Both must
     * still be there.
     */
    public function test_the_both_flows_refuse_what_they_say_they_refuse(): void
    {
        $controller = $this->source('src/Laravel/Http/Controllers/Dashboard/PassportController.php');

        self::assertMatchesRegularExpression(
            '/Unauthenticated: a passport login must be presented by the session/',
            $controller,
            'the ATTACH callback must still refuse 401 without a bound session',
        );
        self::assertStringContainsString(
            'must be started from a browser session to bind to',
            $controller,
            'the anonymous start must still refuse without a session to bind to',
        );
        self::assertStringContainsString(
            'must be finished by the browser session that started it',
            $controller,
            'the anonymous callback must still refuse without a session to bind to',
        );
        self::assertStringContainsString('already authenticated', $controller, 'a live session must be refused, not replaced');
    }
}
