<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Message;

use Illuminate\Filesystem\Filesystem;
use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use MeRezaRezaei\Teleframe\Message\MessageFactory;
use PHPUnit\Framework\TestCase;

/**
 * The `message('name')` finder: static and instance forms resolve a named
 * template to the compiled entity plan, and `media()` runs the Q14 closure
 * fallback (validated, never cached).
 */
final class MessageFactoryTest extends TestCase
{
    private string $root;
    private string $templatesDir;
    private string $cacheDir;
    private string $manifestPath;
    private Filesystem $filesystem;
    private MessageFactory $factory;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->root = sys_get_temp_dir() . '/teleframe-factory-' . str_replace('.', '', uniqid('', true));
        $this->templatesDir = $this->root . '/resources/views/telegram';
        $this->cacheDir = $this->root . '/bootstrap/cache/telegram-templates';
        $this->filesystem->makeDirectory($this->templatesDir, 0755, true);
        $this->manifestPath = $this->root . '/schema-manifest.json';
        $this->filesystem->put($this->manifestPath, '{"layer": 200}');

        foreach ($this->filesystem->files(__DIR__ . '/fixtures/templates') as $fixture) {
            $this->filesystem->copy($fixture->getPathname(), $this->templatesDir . '/' . $fixture->getFilename());
        }

        $compiler = new MessageCompiler($this->templatesDir, $this->cacheDir, $this->filesystem, $this->manifestPath);
        $this->factory = new MessageFactory($compiler);
    }

    protected function tearDown(): void
    {
        $this->filesystem->deleteDirectory($this->root);
    }

    public function test_static_message_finder_returns_the_plan(): void
    {
        $compiler = new MessageCompiler($this->templatesDir, $this->cacheDir, $this->filesystem, $this->manifestPath);

        $plan = MessageFactory::message('greeting', ['name' => 'Ada'], $compiler);

        self::assertSame('Hello, Ada!', $plan['text']);
        self::assertSame([], $plan['entities']);
    }

    public function test_instance_resolve_and_invoke(): void
    {
        self::assertSame('Hello, world!', $this->factory->resolve('greeting')['text']);
        $invoke = $this->factory;
        self::assertSame('Hello, world!', $invoke('greeting')['text']);
    }

    public function test_factory_compiles_entities_and_reply_markup(): void
    {
        $plan = $this->factory->resolve('markup');

        self::assertSame('Buy now', $plan['text']);
        self::assertSame('messageEntityBold', $plan['entities'][0]['_']);
        self::assertArrayHasKey('inline_keyboard', $plan['reply_markup']);
    }

    public function test_media_closure_fallback_is_validated_and_not_cached(): void
    {
        $plan = $this->factory->media('album', static fn (array $data): array => [
            'text' => 'album ' . ($data['slug'] ?? ''),
        ], ['slug' => 'summer-trip']);

        self::assertSame('album summer-trip', $plan['text']);
        self::assertSame([], $plan['entities']);

        $compiler = new MessageCompiler($this->templatesDir, $this->cacheDir, $this->filesystem, $this->manifestPath);
        self::assertSame([], $compiler->cacheStore()->files());
    }

    public function test_finder_caches_across_calls(): void
    {
        $invoke = $this->factory;
        $invoke('greeting');

        $compiler = new MessageCompiler($this->templatesDir, $this->cacheDir, $this->filesystem, $this->manifestPath);

        self::assertCount(1, $compiler->cacheStore()->files());
    }
}