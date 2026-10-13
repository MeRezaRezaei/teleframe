<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\Laravel\Facades\TelegramApp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * DEFECT-6 — the facade's own worked example passed the wrong argument.
 *
 * `TelegramApp.php:44` read
 *
 *     $recent = TelegramApp::read($u->accountId, 20);
 *
 * but `AppRuntime::read()`'s FIRST parameter is `$peer` (a chat id, username or
 * a normalized peer array) — the account id is what `answer()` takes, not
 * `read()`. So the documented example queried history with the ACCOUNT ID AS IF
 * IT WERE A PEER: a call that type-checks (the parameter is
 * `int|string|array`) and returns rows for whatever peer `1234567` resolves to.
 * A wrong-but-valid example is worse than a fatal one, because it runs.
 *
 * The runnable `examples/ai-read-and-reply.php` was already correct and is left
 * alone; only the docblock was wrong.
 *
 * The sibling `TelegramAppFacadeContractTest` already resolves every method NAME
 * in this docblock — which is exactly why this defect survived it: `read` exists,
 * so the check passed. What was needed was a check on the ARGUMENT.
 */
final class TelegramAppDocblockReadExampleTest extends TestCase
{
    /** The first argument expression of `TelegramApp::read(…)` in the class docblock. */
    private function firstReadArgument(): string
    {
        $doc = (new \ReflectionClass(TelegramApp::class))->getDocComment();

        self::assertIsString($doc);

        self::assertSame(1, preg_match_all('/TelegramApp::read\(([^,]*),/', $doc, $matches),
            'the facade docblock must still carry exactly one worked read() example');

        return trim($matches[1][0]);
    }

    #[Test]
    public function the_docblock_read_example_passes_a_peer_not_an_account_id(): void
    {
        $argument = $this->firstReadArgument();

        self::assertStringNotContainsString('accountId', $argument,
            'AppRuntime::read() takes a PEER first; passing $u->accountId queries the account id as a peer id');

        self::assertStringContainsString('peer_id', $argument,
            'the example must read the peer off the update, the way examples/ai-read-and-reply.php does');
    }

    /**
     * The parameter NAMES the example's arguments have to agree with, read from
     * the real signature rather than restated here.
     */
    #[Test]
    public function the_example_agrees_with_the_real_signature(): void
    {
        $parameters = (new \ReflectionMethod(AppRuntime::class, 'read'))->getParameters();

        self::assertSame('peer', $parameters[0]->getName(),
            'the first argument of read() is the peer — the docblock example must not contradict it');
        self::assertSame('limit', $parameters[1]->getName());

        $answer = (new \ReflectionMethod(AppRuntime::class, 'answer'))->getParameters();

        self::assertSame('accountId', $answer[0]->getName(),
            'the account id is answer()\'s first argument, which is why the example\'s two calls differ');
    }

    /**
     * The docblock as a whole must stay free of the account-id-as-peer mistake
     * in ANY read example, so a future edit cannot reintroduce it silently.
     */
    #[Test]
    public function no_read_example_in_the_docblock_queries_with_an_account_id(): void
    {
        $doc = (new \ReflectionClass(TelegramApp::class))->getDocComment();

        self::assertIsString($doc);

        preg_match_all('/TelegramApp::read\(([^)]*)\)/', $doc, $matches);

        foreach ($matches[1] as $arguments) {
            self::assertStringNotContainsString('accountId', $arguments,
                'every worked read() example must pass a peer, not an account id');
        }
    }
}
