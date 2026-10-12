<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Message;

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Message\Exceptions\InvalidMessagePlanException;
use MeRezaRezaei\Teleframe\Message\Exceptions\MessageTemplateException;
use MeRezaRezaei\Teleframe\Message\FilesystemCache;
use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use PHPUnit\Framework\TestCase;

/**
 * Q14: compile a named PHP template to a cached entity plan, salted by the
 * schema layer (`SchemaLayer::cacheSalt()` — a layer bump recompiles even
 * when no mtime changed). Covers cache hit/miss, salt invalidation,
 * forget/flush, the Q14 closure fallback, and compile-time typechecking.
 */
final class MessageCompilerTest extends TestCase
{
    private string $root;
    private string $templatesDir;
    private string $cacheDir;
    private string $manifestPath;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->root = sys_get_temp_dir() . '/teleframe-msg-' . str_replace('.', '', uniqid('', true));
        $this->templatesDir = $this->root . '/resources/views/telegram';
        $this->cacheDir = $this->root . '/bootstrap/cache/telegram-templates';
        $this->filesystem->makeDirectory($this->templatesDir, 0755, true);

        $this->manifestPath = $this->root . '/schema-manifest.json';
        $this->filesystem->put($this->manifestPath, '{"layer": 200}');

        $this->copyFixtures();
    }

    protected function tearDown(): void
    {
        $this->filesystem->deleteDirectory($this->root);
    }

    public function test_compile_runs_template_with_data(): void
    {
        $compiler = $this->compiler();

        $plan = $compiler->compile('greeting', ['name' => 'Ada']);

        self::assertSame('Hello, Ada!', $plan['text']);
        self::assertSame([], $plan['entities']);
    }

    public function test_compile_defaults_missing_data(): void
    {
        $compiler = $this->compiler();

        $plan = $compiler->compile('greeting');

        self::assertSame('Hello, world!', $plan['text']);
    }

    public function test_compile_returns_entities_and_reply_markup(): void
    {
        $compiler = $this->compiler();

        $plan = $compiler->compile('markup');

        self::assertSame('Buy now', $plan['text']);
        self::assertSame('messageEntityBold', $plan['entities'][0]['_']);
        self::assertSame('messageEntityTextUrl', $plan['entities'][1]['_']);
        self::assertSame('inline_keyboard', array_key_first($plan['reply_markup']));
    }

    public function test_cache_hit_serves_without_re_executing_the_template(): void
    {
        $compiler = $this->compiler();
        $stamp = $this->templatesDir . '/executed.txt';

        self::assertSame(['text' => 'stamped', 'entities' => []], $compiler->compile('stamp'));
        self::assertFileExists($stamp);
        $this->filesystem->delete($stamp);

        self::assertSame(['text' => 'stamped', 'entities' => []], $compiler->compile('stamp'));
        self::assertFileDoesNotExist($stamp);
    }

    public function test_salt_bump_recompiles_without_mtime_change(): void
    {
        $compiler = $this->compiler();

        self::assertSame('schema-layer-200', $compiler->cacheSalt());

        self::assertSame(['text' => 'stamped', 'entities' => []], $compiler->compile('stamp'));
        self::assertCount(1, $compiler->cacheStore()->files());
        $oldCacheFile = $compiler->cacheStore()->files()[0];
        $oldMtime = $this->filesystem->lastModified($oldCacheFile);

        $this->filesystem->delete($this->templatesDir . '/executed.txt');

        // Same salt -> the compiled plan is served from cache (no re-execution).
        self::assertSame(['text' => 'stamped', 'entities' => []], $compiler->compile('stamp'));
        self::assertFileDoesNotExist($this->templatesDir . '/executed.txt');

        // Bump the schema layer WITHOUT touching any file mtime: the salt
        // string changes, orphan the old artifact, recompile on demand.
        $this->bumpLayer(201);
        self::assertSame('schema-layer-201', $compiler->cacheSalt());

        self::assertSame(['text' => 'stamped', 'entities' => []], $compiler->compile('stamp'));

        // Template WAS re-executed after the salt change...
        self::assertFileExists($this->templatesDir . '/executed.txt');
        // ...the old artifact is orphaned (a second file), not evicted...
        self::assertCount(2, $compiler->cacheStore()->files());
        // ...and its mtime never moved — invalidation came from the salt, not mtime.
        self::assertSame($oldMtime, $this->filesystem->lastModified($oldCacheFile));
        self::assertFileExists($oldCacheFile);

        // Change of template file mtime alone must NOT invalidate the cache.
        touch($this->templatesDir . '/stamp.php');
        self::assertCount(2, $compiler->cacheStore()->files());
        self::assertSame(['text' => 'stamped', 'entities' => []], $compiler->compile('stamp'));
        self::assertCount(2, $compiler->cacheStore()->files());
    }

    public function test_forget_removes_a_single_name(): void
    {
        $compiler = $this->compiler();

        $compiler->compile('greeting');
        $compiler->compile('stamp');

        self::assertCount(2, $compiler->cacheStore()->files());

        self::assertTrue($compiler->forget('greeting'));
        self::assertCount(1, $compiler->cacheStore()->files());
        self::assertFalse($compiler->forget('greeting'));

        // The surviving name still hits the cache.
        $marker = $this->templatesDir . '/executed.txt';
        $this->filesystem->delete($marker);
        $compiler->compile('stamp');
        self::assertFileDoesNotExist($marker);
    }

    public function test_flush_clears_the_whole_store(): void
    {
        $compiler = $this->compiler();

        $compiler->compile('greeting');
        $compiler->compile('markup');

        self::assertCount(2, $compiler->cacheStore()->files());
        self::assertTrue($compiler->flush());
        self::assertSame([], $compiler->cacheStore()->files());
    }

    public function test_unknown_template_throws_with_its_name(): void
    {
        $compiler = $this->compiler();

        try {
            $compiler->compile('ghost');
            self::fail('Expected MessageTemplateException.');
        } catch (MessageTemplateException $e) {
            self::assertSame('ghost', $e->name);
        }
    }

    public function test_template_returning_non_array_throws(): void
    {
        $compiler = $this->compiler();
        $this->writeTemplate('bad', '<?php return "nope";');

        $this->expectException(MessageTemplateException::class);

        $compiler->compile('bad');
    }

    public function test_unsafe_template_name_is_rejected(): void
    {
        $compiler = $this->compiler();

        $this->expectException(InvalidArgumentException::class);

        $compiler->compile('../etc/passwd');
    }

    public function test_invalid_plan_is_rejected_at_compile_time(): void
    {
        $compiler = $this->compiler();

        $this->expectException(InvalidMessagePlanException::class);

        $compiler->compile('invalid_key');
    }

    public function test_compile_from_runs_a_closure_without_caching(): void
    {
        $compiler = $this->compiler();

        $plan = $compiler->compileFrom('media', static fn (array $data): array => [
            'text' => 'media for ' . ($data['id'] ?? '?'),
        ], ['id' => 'album-1']);

        self::assertSame('media for album-1', $plan['text']);
        self::assertSame([], $compiler->cacheStore()->files());
    }

    public function test_compile_from_rejects_non_array_result(): void
    {
        $compiler = $this->compiler();

        $this->expectException(MessageTemplateException::class);

        $compiler->compileFrom('media', static fn (array $data): string => 'nope');
    }

    public function test_templates_and_cache_dirs_are_exposed(): void
    {
        $compiler = $this->compiler();

        self::assertSame($this->templatesDir, $compiler->templatesDir());
        self::assertSame($this->cacheDir, $compiler->cacheDir());
        self::assertInstanceOf(FilesystemCache::class, $compiler->cacheStore());
    }

    private function compiler(): MessageCompiler
    {
        return new MessageCompiler($this->templatesDir, $this->cacheDir, $this->filesystem, $this->manifestPath);
    }

    private function copyFixtures(): void
    {
        foreach ($this->filesystem->files(__DIR__ . '/fixtures/templates') as $fixture) {
            $this->filesystem->copy($fixture->getPathname(), $this->templatesDir . '/' . $fixture->getFilename());
        }
    }

    private function writeTemplate(string $name, string $php): void
    {
        $this->filesystem->put($this->templatesDir . '/' . $name . '.php', $php);
    }

    private function bumpLayer(int $layer): void
    {
        $this->filesystem->put($this->manifestPath, '{"layer": ' . $layer . '}');
    }
}