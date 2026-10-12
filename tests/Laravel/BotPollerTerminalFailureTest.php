<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel;

use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;
use MeRezaRezaei\Teleframe\Laravel\Services\UpdatePollerService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

/**
 * A WRONG BOT TOKEN MUST NOT SPIN FOREVER.
 *
 * `pollBot()` had its own inline `catch (Throwable)`, separate from the policy
 * the user loop uses. It swallowed everything and retried, so the two obvious
 * first-run mistakes became an infinite 2-second sleep that exited 0 and looked
 * perfectly healthy:
 *
 *  - a typo'd token, which Telegram answers with 401 Unauthorized
 *  - any other permanent Bot API failure
 *
 * A missing token was already loud — `bot()` throws before the loop — so the
 * dangerous half was the token that EXISTS and is WRONG, which is the more
 * likely mistake and the quieter one.
 *
 * `pollBot` now routes its catch through the same `errorLoopAction()` the user
 * loop uses, so "what counts as terminal" is decided in exactly one place. A
 * test that exercises `errorLoopAction` directly, because an infinite retry
 * loop cannot be asserted by running it.
 */
final class BotPollerTerminalFailureTest extends TestCase
{
    private const BOT_UNAUTHORIZED = 401;

    public function test_a_bot_api_401_is_terminal_not_retried(): void
    {
        $action = $this->policy(new TelegramException('Telegram Bot API [401]: Unauthorized', self::BOT_UNAUTHORIZED));

        $this->assertSame(
            'rethrow',
            $action,
            'a wrong bot token can never be fixed by retrying, so it must surface instead of spinning',
        );
    }

    public function test_a_flood_wait_is_still_retried(): void
    {
        $action = $this->policy(new TelegramException('Telegram Bot API [429]: Too Many Requests', 429));

        $this->assertSame(
            'backoff',
            $action,
            'a rate limit IS worth retrying — the fix must not make transient failures terminal',
        );
    }

    public function test_a_transient_error_is_still_retried(): void
    {
        $action = $this->policy(new RuntimeException('connection reset by peer'));

        $this->assertSame('backoff', $action, 'a network blip must keep its backoff');
    }

    public function test_the_bot_loop_uses_the_shared_policy_and_not_its_own_catch(): void
    {
        // The regression is structural: a second, laxer policy in a sibling
        // loop. Assert pollBot actually consults errorLoopAction(), so a future
        // bespoke catch fails here rather than in production.
        $source = (string) file_get_contents(
            dirname(__DIR__, 2).'/src/Laravel/Services/UpdatePollerService.php',
        );

        $start = strpos($source, 'public function pollBot');
        $this->assertIsInt($start, 'pollBot must exist');

        // Scope to pollBot's OWN body. A fixed character window is fragile: it
        // silently starts overlapping the next method as pollBot grows, and a
        // neighbouring method's code then decides whether this assertion
        // passes. That is not a test.
        $next = strpos($source, 'public function ', $start + 20);
        $body = $next === false
            ? substr($source, $start)
            : substr($source, $start, $next - $start);

        $catchAt = strpos($body, '} catch');
        $this->assertIsInt($catchAt, 'pollBot must have a catch block');

        $catchBlock = substr($body, $catchAt);

        $this->assertStringContainsString(
            '$this->errorLoopAction($e)',
            $catchBlock,
            'pollBot must route its failures through the shared policy, not a bespoke catch',
        );
        $this->assertStringNotContainsString(
            'if (! $this->running) {',
            $catchBlock,
            'the old inline catch-only policy must be gone from pollBot',
        );
    }

    /** The loop's own decision, reached without running an infinite loop. */
    private function policy(\Throwable $e): string
    {
        $method = new ReflectionMethod(UpdatePollerService::class, 'errorLoopAction');

        /** @var UpdatePollerService $service */
        $service = (new ReflectionClass(UpdatePollerService::class))->newInstanceWithoutConstructor();

        return (string) $method->invoke($service, $e);
    }
}
