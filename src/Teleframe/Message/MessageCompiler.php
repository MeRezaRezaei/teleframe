<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Message;

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Core\Schema\SchemaLayer;
use MeRezaRezaei\Teleframe\Message\Exceptions\MessageTemplateException;

/**
 * Compiles named message templates (plain PHP flat files) into the entity
 * plan `{text, entities, reply_markup?}` and caches that plan in a
 * `bootstrap/cache`-style filesystem store (Q14). The plan — not the
 * template code — is the cache artifact, so text sends hit the fast path;
 * media/album senders fall back to `compileFrom()` closures because a text
 * plan cannot describe them (Q14 closure fallback).
 *
 * Cache invalidation (hard constraint 4 / Phase 1): every cache key is
 * salted with `SchemaLayer::cacheSalt()`. A layer bump changes the salt
 * string, which changes the key → the old entry becomes an orphan and the
 * template recompiles, EVEN when no file mtime changed. This is the exact
 * mtime-independent invalidation ruled in the gap analysis.
 *
 * The default template directory is the package-resolution of a
 * `resources/views/telegram/`-style location; the default cache directory is
 * the `bootstrap/cache/telegram-templates` analogue. Both are constructor
 * seams (plain-PHP hosts and tests point them at their own paths).
 */
final class MessageCompiler
{
    private readonly string $templatesDir;

    private readonly string $cacheDir;

    private readonly Filesystem $filesystem;

    private readonly FilesystemCache $cache;

    private readonly ?string $schemaManifestPath;

    public function __construct(
        ?string $templatesDir = null,
        ?string $cacheDir = null,
        ?Filesystem $filesystem = null,
        ?string $schemaManifestPath = null,
    ) {
        $root = dirname(__DIR__, 3);

        $this->templatesDir = $templatesDir ?? $root . '/resources/views/telegram';
        $this->cacheDir = $cacheDir ?? $root . '/bootstrap/cache/telegram-templates';
        $this->filesystem = $filesystem ?? new Filesystem();
        $this->schemaManifestPath = $schemaManifestPath;
        $this->cache = new FilesystemCache($this->cacheDir, $this->filesystem);
    }

    /**
     * Compile a named template to the cached entity plan.
     *
     * The template is a PHP file returning an array — plain PHP, no new
     * markup parser (Q13). It receives the data array as `$data` (extracted
     * into the local scope, Laravel PhpEngine-style), so
     * `message('welcome', ['name' => 'Ada'])` resolves `$name` inside the
     * template.
     *
     * @return array{text: string, entities: list<array<string, mixed>>, reply_markup?: array<string, mixed>}
     *
     * @throws MessageTemplateException when the template is missing or does not return an array
     * @throws InvalidArgumentException  when the template name is unsafe
     */
    public function compile(string $name, array $data = []): array
    {
        $this->assertTemplateName($name);

        $key = $this->cacheKey($name);

        $cached = $this->cache->get($key);
        if (is_array($cached)) {
            return MessagePlanValidator::assertPlanValid($cached);
        }

        $path = $this->templatesDir . '/' . $name . '.php';
        if (! $this->filesystem->isFile($path)) {
            throw new MessageTemplateException(
                "Message template [{$name}] not found at [{$path}].",
                $name,
            );
        }

        $rendered = $this->render($path, $name, $data);

        $plan = MessagePlanValidator::assertPlanValid($rendered);

        $this->cache->set($key, $plan);

        return $plan;
    }

    /**
     * Q14 closure fallback: run an arbitrary factory (media, albums, or any
     * send whose payload cannot be named) to an entity plan. Validated but
     * NOT cached — there is no name-keyed artifact to reuse.
     *
     * @param string                                  $name    logical label for errors
     * @param callable(array<string, mixed>): mixed   $factory receives `$data`
     * @return array{text: string, entities: list<array<string, mixed>>, reply_markup?: array<string, mixed>}
     */
    public function compileFrom(string $name, callable $factory, array $data = []): array
    {
        $this->assertTemplateName($name);

        $result = $factory($data);
        if (! is_array($result)) {
            throw new MessageTemplateException(
                "Template closure [{$name}] did not return an array.",
                $name,
            );
        }

        return MessagePlanValidator::assertPlanValid($result);
    }

    /**
     * view:clear-style op for ONE template name: drop its current-salt cache
     * entry. Old-salt orphans left by layer bumps are intentionally untouched
     * — `flush()` removes everything.
     */
    public function forget(string $name): bool
    {
        $this->assertTemplateName($name);

        return $this->cache->delete($this->cacheKey($name));
    }

    /** Drop every compiled-plan entry (the whole store directory). */
    public function flush(): bool
    {
        return $this->cache->clear();
    }

    /** The schema-layer salt this compiler keys its caches with. */
    public function cacheSalt(): string
    {
        return SchemaLayer::cacheSalt($this->schemaManifestPath);
    }

    /** Absolute path of the cache directory backing this compiler. */
    public function cacheDir(): string
    {
        return $this->cacheDir;
    }

    /** Absolute path of the templates directory this compiler reads. */
    public function templatesDir(): string
    {
        return $this->templatesDir;
    }

    /** The filesystem store backing the compiled-plan cache. */
    public function cacheStore(): FilesystemCache
    {
        return $this->cache;
    }

    private function cacheKey(string $name): string
    {
        return sha1($this->cacheSalt() . '|' . $name);
    }

    /**
     * @return array<string, mixed>
     */
    private function render(string $path, string $name, array $data): array
    {
        $render = static function (array $data, string $path): mixed {
            extract($data, EXTR_SKIP);

            return include $path;
        };

        $result = $render($data, $path);
        if (! is_array($result)) {
            throw new MessageTemplateException(
                "Message template [{$name}] did not return an array.",
                $name,
            );
        }

        return $result;
    }

    private function assertTemplateName(string $name): void
    {
        if ($name === '' || str_contains($name, '/') || str_contains($name, '\\') || str_starts_with($name, '..')) {
            throw new InvalidArgumentException("Unsafe message template name [{$name}].");
        }
    }
}