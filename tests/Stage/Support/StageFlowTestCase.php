<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Stage\Support;

use Illuminate\Filesystem\Filesystem;
use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use MeRezaRezaei\Teleframe\Message\MessageFactory;
use MeRezaRezaei\Teleframe\Stage\StageValidator;
use PHPUnit\Framework\TestCase;

/**
 * Shared harness for stage flow tests: compiles the fixture templates into a
 * per-test temp dir (MessageCompiler salt seam) so middleware plans resolve
 * out of thin air, plus the standalone validator used by both legs.
 */
abstract class StageFlowTestCase extends TestCase
{
    protected Filesystem $filesystem;
    protected string $root;
    protected string $templatesDir;
    protected string $cacheDir;
    protected MessageFactory $messages;
    protected StageValidator $validator;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->root = sys_get_temp_dir() . '/teleframe-stageflow-' . str_replace('.', '', uniqid('', true));
        $this->templatesDir = $this->root . '/templates';
        $this->cacheDir = $this->root . '/cache';
        $this->filesystem->makeDirectory($this->templatesDir, 0755, true);

        $this->filesystem->copyDirectory(dirname(__DIR__) . '/Fixtures/templates', $this->templatesDir);
        $this->filesystem->put($this->root . '/schema-manifest.json', '{"layer": 200}');

        $compiler = new MessageCompiler(
            $this->templatesDir,
            $this->cacheDir,
            $this->filesystem,
            $this->root . '/schema-manifest.json',
        );
        $this->messages = new MessageFactory($compiler);
        $this->validator = new StageValidator();
    }

    protected function tearDown(): void
    {
        $this->filesystem->deleteDirectory($this->root);
    }
}