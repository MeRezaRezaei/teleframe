<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use PHPUnit\Framework\TestCase;

/**
 * The generated endpoint coverage must match a fresh classification.
 *
 * Shells out to the generator's `--check` mode rather than regenerating and
 * diffing here: `--check` leaves the tracked file untouched, so a test run can
 * never mutate the document it is asserting about. A test that regenerates its
 * own subject and then compares is comparing the file to itself.
 */
final class EndpointCoverageDocIsCurrentTest extends TestCase
{
    public function test_the_generated_document_matches_a_fresh_classification(): void
    {
        $generator = $this->generatorPath();

        self::assertFileExists($generator, 'the generator is the subject of this gate; if it moved, fix the path');

        [$exit, $output] = $this->invokeGenerator($generator, '--check');

        self::assertSame(
            0,
            $exit,
            "docs/schema/endpoint-coverage.md is stale.\n".$output
            ."\nRun: php bin/generate-endpoint-coverage.php",
        );
        self::assertStringContainsString('current', $output, '--check exited 0 without reporting the document current');
    }

    public function test_the_generator_rejects_an_unknown_flag(): void
    {
        // A generator that ignores its arguments cannot be trusted to honour
        // --check, and a --check that never fails is not a gate.
        [$exit, $output] = $this->invokeGenerator($this->generatorPath(), '--nonsense');

        self::assertSame(2, $exit, 'an unknown flag must exit 2 (usage), not run anyway. Output: '.$output);
    }

    public function test_the_generator_writes_no_file_when_checking(): void
    {
        // The control half of the gate above: --check must be READ-ONLY. A
        // --check that rewrites the document it is checking would make the
        // staleness assertion self-healing and therefore meaningless.
        $doc = dirname(__DIR__, 2).'/docs/schema/endpoint-coverage.md';
        self::assertFileExists($doc, 'the generated document is the subject of this gate');

        $before = (string) file_get_contents($doc);
        $beforeMtime = filemtime($doc);

        [$exit] = $this->invokeGenerator($this->generatorPath(), '--check');
        self::assertSame(0, $exit, '--check must pass on a freshly generated document');

        clearstatcache(true, $doc);
        self::assertSame($before, (string) file_get_contents($doc), '--check must not alter the document');
        self::assertSame($beforeMtime, filemtime($doc), '--check must not even touch the document mtime');
    }

    private function generatorPath(): string
    {
        return dirname(__DIR__, 2).'/bin/generate-endpoint-coverage.php';
    }

    /**
     * @return array{0:int,1:string} exit code and combined output
     */
    private function invokeGenerator(string $generator, string $flag): array
    {
        $output = [];
        $exit = 0;
        exec(sprintf('timeout -k 5 300 php %s %s 2>&1', escapeshellarg($generator), escapeshellarg($flag)), $output, $exit);

        return [$exit, implode("\n", $output)];
    }
}
