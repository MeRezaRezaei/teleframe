<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Laravel\AppServiceProvider;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * F16-2 — THE ONLY `define()` EXAMPLE IN docs/ FATALED, AND THE "CORRECTION"
 * BENEATH IT WAS THE STALE HALF.
 *
 * `docs/usage/integration.md` §4.5 read
 *
 *     AppDefinition::define('echo-bot', AppMode::Bot, triggers: [
 *         ['match' => '/start', 'handler' => \App\Telegram\EchoHandler::class],
 *     ])
 *
 * — a plain ARRAY for a trigger row. `AppDefinition::define()` requires
 * `AppTrigger` instances and refuses anything else at declare time, so the one
 * code block in `docs/` demonstrating the capability a host would copy first
 * threw `InvalidArgumentException: app [echo-bot] trigger list may only contain
 * AppTrigger instances, got array at index 0` on its first executed line.
 *
 * The note under it was worse in the other direction: it told hosts to avoid
 * `define()`, `list()` and `calls()` because "none of the three exist on
 * AppRuntime". All three DO exist (`AppRuntime.php:79`, `:91`, `:103`) — added
 * by the 2026-09-26 reachability fix — so the doc pushed readers away from the
 * working sugar. Only the `service()` half was true.
 *
 * So the doc is EXECUTED here, extracted from the file itself: every fenced
 * php block in §4.5 is run against a real container, and every facade method
 * the section names must exist on `AppRuntime`. A stale claim cannot survive,
 * because a claim that names a missing method fails the second half and a
 * block that would fatal fails the first.
 */
final class TelegramAppIntegrationDocExampleTest extends TestCase
{
    private const DOC = __DIR__.'/../../docs/usage/integration.md';

    /**
     * Methods §4.5 is ALLOWED to name as absent, with the reason. A method on
     * this list stays exempt from the exists-check; if it ever ships, the test
     * fails and the doc gets its warning corrected rather than left stale
     * again.
     *
     * @var list<string>
     */
    private const DOCUMENTED_AS_MISSING = ['service'];

    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [AppServiceProvider::class];
    }

    protected function setUp(): void
    {
        HandlerRegistry::reset();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        HandlerRegistry::reset();

        parent::tearDown();
    }

    /**
     * Every fenced php block in the §4.5 section, verbatim, in document order.
     *
     * @return list<string>
     */
    private static function sectionBlocks(): array
    {
        $markdown = (string) file_get_contents(self::DOC);
        self::assertIsString($markdown, 'docs/usage/integration.md must be readable');

        $start = strpos($markdown, '### 4.5 `TelegramApp`');
        self::assertNotFalse($start, 'the §4.5 section must exist — a renamed or deleted section must fail here');

        $end = strpos($markdown, "\n### ", $start + 1);
        $section = $end === false ? substr($markdown, $start) : substr($markdown, $start, $end - $start);

        preg_match_all('/```php\n(.*?)```/s', $section, $matches);

        self::assertNotEmpty($matches[1], '§4.5 must carry at least one runnable php example');

        return array_values($matches[1]);
    }

    /** The §4.5 prose + code, for the claims made about the surface. */
    private static function section(): string
    {
        $markdown = (string) file_get_contents(self::DOC);
        $start = strpos($markdown, '### 4.5 `TelegramApp`');
        self::assertNotFalse($start);

        $end = strpos($markdown, "\n### ", $start + 1);

        return $end === false ? substr($markdown, $start) : substr($markdown, $start, $end - $start);
    }

    /**
     * THE headline: the documented example RUNS. It is extracted from the
     * markdown and executed against a real container — no hand-written copy in
     * this file that could drift from the doc it claims to check.
     */
    public function test_every_documented_example_block_executes(): void
    {
        $blocks = self::sectionBlocks();

        foreach ($blocks as $index => $block) {
            try {
                eval($block);
            } catch (\Throwable $e) {
                self::fail(sprintf(
                    'docs/usage/integration.md §4.5 block #%d throws %s: %s — the doc example must run.',
                    $index + 1,
                    $e::class,
                    $e->getMessage(),
                ));
            }
        }

        $runtime = $this->app->make('teleclient.app.service');

        self::assertInstanceOf(AppRuntime::class, $runtime);
        self::assertNotEmpty($runtime->apps(), 'the example registered what it claims to register');

        // And the declared trigger is really on the shared hot table under the
        // app's tag — the example is not a no-op that merely avoids throwing.
        $tags = array_map(static fn ($row): ?string => $row->tag, HandlerRegistry::instance()->all());
        self::assertContains('app:echo-bot', $tags, 'the first block declared echo-bot with AppTrigger::on()');
    }

    /**
     * A trigger row is an AppTrigger, never a plain array — pinned so the exact
     * shape that fataled cannot come back into the docs.
     */
    public function test_no_documented_trigger_row_is_a_plain_array(): void
    {
        foreach (self::sectionBlocks() as $block) {
            self::assertDoesNotMatchRegularExpression(
                '/triggers:\s*\[\s*\[\s*[\'"]match[\'"]/',
                $block,
                'a trigger row is AppTrigger::on(...), not a plain array — define() refuses an array at declare time',
            );
        }
    }

    /**
     * Every `TelegramApp::method(` the section names must EXIST on AppRuntime,
     * except the ones the section explicitly documents as missing. This is the
     * check that kills the "none of the three exist" class of claim.
     */
    public function test_every_facade_method_the_section_names_exists_on_the_runtime(): void
    {
        preg_match_all('/TelegramApp::([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', self::section(), $matches);

        self::assertNotEmpty($matches[1], '§4.5 must show at least one facade call');

        foreach (array_unique($matches[1]) as $method) {
            if (in_array($method, self::DOCUMENTED_AS_MISSING, true)) {
                self::assertFalse(
                    method_exists(AppRuntime::class, $method),
                    'TelegramApp::'.$method.'() is on the documented-as-missing list but now EXISTS on AppRuntime'
                    .' — the §4.5 warning is stale and must be corrected in the same commit that shipped the method',
                );

                continue;
            }

            self::assertTrue(
                method_exists(AppRuntime::class, $method),
                'the doc shows TelegramApp::'.$method.'(), which does not exist on AppRuntime — the example fatals',
            );
        }
    }

    /**
     * The stale correction must be GONE, not merely supplemented: a reader who
     * believes "define() does not exist" will not use the working sugar.
     */
    public function test_the_section_makes_no_claim_that_define_list_or_calls_are_missing(): void
    {
        $section = self::section();

        foreach (['None of the three exist', 'none of the three exist', 'no `__call` to forward them'] as $lie) {
            self::assertStringNotContainsStringIgnoringCase($lie, $section,
                'the §4.5 correction that denied define()/list()/calls() is false and must not be in docs/');
        }
    }
}
