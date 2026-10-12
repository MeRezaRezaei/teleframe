<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Stage;

use Illuminate\Filesystem\Filesystem;
use MeRezaRezaei\Teleframe\Message\FilesystemCache;
use MeRezaRezaei\Teleframe\Stage\StageRegistry;
use MeRezaRezaei\Teleframe\Stage\StageSet;
use MeRezaRezaei\Teleframe\Stage\StageState;
use PHPUnit\Framework\TestCase;

/**
 * StageState (Phase 5e, Q17): plain-array state over PSR-16. Survival proofs —
 * a second instance over the same store AND a real separate PHP process
 * (supervisor restart) both resume the exact flow from the persisted state.
 */
final class StageStateTest extends TestCase
{
    private Filesystem $filesystem;
    private string $dir;

    private StageRegistry $registry;
    private StageSet $set;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->dir = sys_get_temp_dir() . '/teleframe-stage-' . str_replace('.', '', uniqid('', true));
        $this->filesystem->makeDirectory($this->dir, 0755, true);

        $this->set = StageSet::define('updateNewMessage', [
            'email' => ['steps' => ['email']],
            'profile' => ['steps' => ['name', 'bio']],
            'confirm' => ['steps' => ['tos'], 'final_step' => true],
        ])->compile();
        $this->registry = (new StageRegistry())->on($this->set)->compile();
    }

    protected function tearDown(): void
    {
        $this->filesystem->deleteDirectory($this->dir);
    }

    private function state(): StageState
    {
        return new StageState(new FilesystemCache($this->dir), $this->registry);
    }

    public function test_start_returns_and_persists_blank_plain_state(): void
    {
        $started = $this->state()->start($this->set, '100');

        self::assertSame('updateNewMessage', $started['stageSet']);
        self::assertNull($started['currentStage']);
        self::assertSame([], $started['data']);
        self::assertSame([], $started['msgIds']);
        self::assertTrue($this->state()->has('100'));
    }

    public function test_current_returns_null_when_no_flow(): void
    {
        self::assertNull($this->state()->current('999'));
        self::assertFalse($this->state()->has('999'));
    }

    public function test_advance_fills_data_and_transitions_stages(): void
    {
        $state = $this->state();
        $state->start($this->set, '100');

        $afterEmail = $state->advance('100', ['email' => 'ada@example.com'], $this->set);
        self::assertSame('profile', $afterEmail['currentStage']);
        self::assertSame(['email' => 'ada@example.com'], $afterEmail['data']);

        $afterName = $state->advance('100', ['name' => 'Ada'], $this->set);
        self::assertSame('profile', $afterName['currentStage']);

        $afterBio = $state->advance('100', ['bio' => 'Compiler'], $this->set);
        self::assertSame('confirm', $afterBio['currentStage']);

        $completed = $state->advance('100', ['tos' => 'yes'], $this->set);
        self::assertNull($completed['currentStage']);
        self::assertSame(
            ['email' => 'ada@example.com', 'name' => 'Ada', 'bio' => 'Compiler', 'tos' => 'yes'],
            $completed['data'],
        );
    }

    public function test_advance_without_resolvable_set_keeps_stage_and_stores_data(): void
    {
        // An empty registry cannot resolve the persisted stageSet, so advance
        // degrades safely: data is stored, the stage pointer is untouched.
        $state = new StageState(new FilesystemCache($this->dir), new StageRegistry());
        $state->start($this->set, '100');

        $next = $state->advance('100', ['email' => 'ada@example.com']);

        self::assertSame(['email' => 'ada@example.com'], $next['data']);
        self::assertNull($next['currentStage']);
    }

    public function test_touch_records_msg_ids_without_duplicates(): void
    {
        $state = $this->state();
        $state->start($this->set, '100');

        $state->touch('100', 12, 13);
        $state->touch('100', 13, 14);

        self::assertSame([12, 13, 14], $state->current('100')['msgIds']);
        $state->touch('999', 99);
        self::assertNull($state->current('999'));
    }

    public function test_finish_drops_state(): void
    {
        $state = $this->state();
        $state->start($this->set, '100');

        $state->finish('100');

        self::assertFalse($state->has('100'));
    }

    public function test_state_survives_a_new_instance_over_the_same_store(): void
    {
        $this->state()->start($this->set, '100');
        $this->state()->advance('100', ['email' => 'ada@example.com'], $this->set);

        $rebooted = $this->state();

        self::assertTrue($rebooted->has('100'));
        $current = $rebooted->current('100');
        self::assertSame('profile', $current['currentStage']);
        self::assertSame(['email' => 'ada@example.com'], $current['data']);
    }

    public function test_state_survives_a_separate_process_over_the_same_store(): void
    {
        $state = $this->state();
        $state->start($this->set, '100');
        $state->advance('100', ['email' => 'ada@example.com'], $this->set);

        $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        $code = 'require $argv[1];'
            . ' $cache = new \MeRezaRezaei\Teleframe\Message\FilesystemCache($argv[2]);'
            . ' $registry = new \MeRezaRezaei\Teleframe\Stage\StageRegistry();'
            . ' $st = new \MeRezaRezaei\Teleframe\Stage\StageState($cache, $registry);'
            . ' echo json_encode($st->current((string) $argv[3]));';

        exec(
            sprintf(
                '%s -r %s %s %s %s 2>&1',
                escapeshellarg(PHP_BINARY),
                escapeshellarg($code),
                escapeshellarg($autoload),
                escapeshellarg($this->dir),
                escapeshellarg('100'),
            ),
            $output,
            $exitCode,
        );

        self::assertSame(0, $exitCode, implode("\n", $output));
        $frame = json_decode(implode('', $output), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('profile', $frame['currentStage']);
        self::assertSame(['email' => 'ada@example.com'], $frame['data']);
        self::assertIsArray($frame['msgIds']);
    }

    public function test_state_keys_are_per_account(): void
    {
        $state = $this->state();
        $state->start($this->set, '100');

        self::assertNull($state->current('101'));
        $state->finish('101');
        self::assertTrue($state->has('100'));
    }
}