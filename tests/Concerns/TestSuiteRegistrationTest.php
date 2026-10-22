<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Concerns;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A test directory that is not registered in phpunit.xml.dist is a test that
 * reads as a safety net and is not one.
 *
 * This suite exists because 355 tests were exactly that. `tests/App`,
 * `tests/Auth`, `tests/Config`, `tests/DataCore` and `tests/Notify` existed on
 * disk, and a bare `vendor/bin/phpunit` — which is what `composer verify` runs,
 * via the `test` script — collects only the registered <testsuite> directories.
 * So none of those tests ran: not locally, and not on the GitLab pipeline, and
 * nothing was red. Every result this repo reported as "the gate" was a gate
 * with five doors painted on.
 *
 * So the check is not "are the current directories registered", which would be
 * true again the moment someone adds a sixth. It is: does EVERY directory under
 * tests/ appear in phpunit.xml.dist? A new area added without a testsuite entry
 * fails here, on the commit that added it, instead of being discovered months
 * later when a bug ships.
 *
 * Two deliberate exemptions, each a real reason rather than a convenience:
 *   - `tests/bootstrap.php` and any non-directory file, since this compares
 *     DIRECTORIES and those are not directories.
 *   - `tests/Concerns` itself, which holds this test and the other
 *     cross-cutting concerns. It is registered (see phpunit.xml.dist) so this
 *     file executes; it is exempted from its own directory comparison only so
 *     the file cannot fail to find itself.
 */
final class TestSuiteRegistrationTest extends TestCase
{
    #[Test]
    public function every_test_directory_is_registered_in_the_phpunit_config(): void
    {
        $root = dirname(__DIR__, 2);
        $config = (string) file_get_contents($root.'/phpunit.xml.dist');

        self::assertNotSame('', $config, 'phpunit.xml.dist must be readable — this suite is the gate for the gate');

        $registered = $this->registeredDirectories($config);
        self::assertNotSame([], $registered, 'no <testsuite> directories parsed — the parse itself is broken, not the repo');

        $unregistered = [];
        foreach ($this->testDirectoriesOnDisk($root) as $relative) {
            // This file's own directory: registered, and exempting it from the
            // comparison keeps a path mistake here from masquerading as a repo
            // problem.
            if ($relative === 'tests/Concerns') {
                continue;
            }

            if (! in_array($relative, $registered, true)) {
                $unregistered[] = $relative;
            }
        }

        self::assertSame(
            [],
            $unregistered,
            "these test directories exist but no <testsuite> registers them, so `composer verify` "
            .'never runs them: '.implode(', ', $unregistered)
            .'. Add a <testsuite> entry to phpunit.xml.dist, or the tests in them are decoration.',
        );
    }

    #[Test]
    public function the_suites_this_file_depends_on_are_themselves_registered(): void
    {
        // If tests/Concerns ever lost its own testsuite entry, the test above
        // would stop running and the gate would go quietly blind. Assert the
        // thing this file depends on, rather than trusting it.
        $config = (string) file_get_contents(dirname(__DIR__, 2).'/phpunit.xml.dist');

        self::assertContains('tests/Concerns', $this->registeredDirectories($config));
    }

    /**
     * @return list<string> every <directory> under a <testsuite>, normalised to
     *                           `tests/Foo` with no trailing slash
     */
    private function registeredDirectories(string $config): array
    {
        $found = [];
        // No regex is needed: the document is a fixed, known shape, and reading
        // it with a tokenizer would be more machinery than the job deserves.
        $document = new \DOMDocument;
        self::assertTrue($document->loadXML($config), 'phpunit.xml.dist must be well-formed XML');

        foreach ($document->getElementsByTagName('testsuite') as $testsuite) {
            foreach ($testsuite->getElementsByTagName('directory') as $directory) {
                $found[] = rtrim(trim($directory->textContent), '/');
            }
        }

        return $found;
    }

    /**
     * @return list<string> every `tests/Foo` directory on disk, sorted
     */
    private function testDirectoriesOnDisk(string $root): array
    {
        $directories = glob($root.'/tests/*', GLOB_ONLYDIR);
        self::assertIsArray($directories, 'glob must return an array');

        $relative = [];
        foreach ($directories as $directory) {
            $relative[] = 'tests/'.basename($directory);
        }

        sort($relative);

        return $relative;
    }
}
