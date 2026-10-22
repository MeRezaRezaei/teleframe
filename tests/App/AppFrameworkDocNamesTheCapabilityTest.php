<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\App\AppRuntime;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * F16-3 — THE PAGE FIVE DOCS LINK TO AS "APP FRAMEWORK" NEVER NAMED THE
 * CAPABILITY.
 *
 * `docs/usage/app-framework.md` is what `integration.md`, `mtproto.md`,
 * `notify.md` and `passport.md` all route a reader to for "make a bot that
 * responds to updates". It documented the substrate and nothing else:
 * `grep -c TelegramApp` → 0, `grep -c 'teleframe\.app'` → 0,
 * `grep -c 'examples/'` → 0. The declarative layer the owner actually asked
 * for — triggers, scheduled calls, `read()`/`answer()`, the worker
 * requirement — was absent from the page named after it.
 *
 * Documentation that omits a shipped capability is not neutral: it routes
 * every reader to the wrong page. So the page's content is gated here, on the
 * same terms the page itself is held to:
 *   - it names the facade, the config namespace and the runnable example;
 *   - every `teleframe.app.*` key it names is a REAL key in the shipped
 *     config file (a doc that invents a knob is worse than one that omits it);
 *   - every `TelegramApp::method(` it shows exists on AppRuntime.
 */
final class AppFrameworkDocNamesTheCapabilityTest extends TestCase
{
    private const DOC = __DIR__.'/../../docs/usage/app-framework.md';

    private const README = __DIR__.'/../../docs/usage/README.md';

    private const CONFIG = __DIR__.'/../../src/Laravel/config/app.php';

    private static function doc(): string
    {
        $markdown = (string) file_get_contents(self::DOC);
        self::assertIsString($markdown, 'docs/usage/app-framework.md must be readable');

        return $markdown;
    }

    #[Test]
    public function the_page_names_the_declarative_layer(): void
    {
        $doc = self::doc();

        self::assertStringContainsString('TelegramApp', $doc, 'the page named for the app framework must name the facade');
        self::assertStringContainsString('teleframe.app', $doc, 'the config namespace must be documented');
        self::assertStringContainsString('examples/ai-read-and-reply.php', $doc,
            'the runnable version of the owner\'s sentence must be linked');
    }

    #[Test]
    public function the_page_documents_the_capability_the_owner_named(): void
    {
        $doc = self::doc();

        foreach ([
            'AppTrigger' => 'a trigger row is declared, not a bare array',
            'AppCall' => 'a scheduled call is declared',
            'AppMode' => 'bot|user mode',
            'read(' => 'read my messages',
            'answer(' => 'answer them',
            'runCall' => 'run a scheduled call',
            'installCalls' => 'arm the schedules',
        ] as $needle => $what) {
            self::assertStringContainsString($needle, $doc, 'the app-framework page must document '.$what);
        }
    }

    /**
     * The refusal is the one thing a host WILL meet (a host catch-all plus an
     * app is ordinary), so the page that never named the capability must at
     * least name this.
     */
    #[Test]
    public function the_page_documents_the_unreachable_trigger_refusal(): void
    {
        $doc = self::doc();

        self::assertStringContainsString('onMessage()', $doc, 'the catch-all that makes a trigger unreachable must be named');
        self::assertStringContainsString('efus', $doc, 'the refusal itself must be documented');
        self::assertStringContainsString('teleframe:app list', $doc, 'the refusal must be reported where a host can read it');
    }

    /** Every config key the page names is a REAL key in the shipped config file. */
    #[Test]
    public function every_config_key_the_page_names_exists_in_the_shipped_config(): void
    {
        preg_match_all('/teleframe\.app\.([a-z_]+)/', self::doc(), $matches);

        self::assertNotEmpty($matches[1], 'the page names at least one teleframe.app.* key');

        /** @var array<string, mixed> $config */
        $config = require self::CONFIG;
        self::assertIsArray($config);

        foreach (array_unique($matches[1]) as $key) {
            self::assertArrayHasKey($key, $config,
                'the page documents teleframe.app.'.$key.', which the shipped config file does not define');
        }
    }

    /** Every facade call the page shows is a REAL method on the runtime. */
    #[Test]
    public function every_facade_method_the_page_shows_exists_on_the_runtime(): void
    {
        preg_match_all('/TelegramApp::([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', self::doc(), $matches);

        self::assertNotEmpty($matches[1], 'the page shows at least one facade call');

        foreach (array_unique($matches[1]) as $method) {
            self::assertTrue(
                method_exists(AppRuntime::class, $method),
                'the page shows TelegramApp::'.$method.'(), which does not exist on AppRuntime',
            );
        }
    }

    /** The index row that routes readers here must name both halves of the page. */
    #[Test]
    public function the_usage_index_row_names_the_declarative_layer(): void
    {
        $readme = (string) file_get_contents(self::README);
        self::assertIsString($readme);

        self::assertSame(1, preg_match('/^\| \[app-framework\.md\].*\|$/m', $readme, $row),
            'docs/usage/README.md must carry exactly one index row for app-framework.md');

        self::assertStringContainsString('TelegramApp', $row[0],
            'the index row routes readers here for the app framework, so it must name the facade');
        self::assertStringContainsString('APP FRAMEWORK', $row[0]);
    }
}
