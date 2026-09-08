<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use MeRezaRezaei\Teleframe\Schema\Generator\SchemaRegenerator;
use MeRezaRezaei\Teleframe\Schema\Generator\TeleframeSchemeLoader;

/** artisan teleframe:regenerate — rebuild the committed mirror from tracked schemes. */
final class RegenerateCommand extends Command
{
    protected $signature = 'teleframe:regenerate
        {--force : Bypass the ±30% constructor-count sanity gate}
        {--schemas= : Scheme directory (default: package schema/sources/ mirror)}
        {--out= : Output directory (default: package root)}
        {--ship : Also publish the curated migration subset to <out>/migrations (dial: config teleframe.ship_namespaces)}';

    protected $description = 'Regenerate migrations/models/DTOs/factories from tracked TL schemes';

    protected $aliases = [];

    public function handle(SchemaRegenerator $engine): int
    {
        $packageRoot = dirname(__DIR__, 3);
        $schemas = (string) ($this->option('schemas') ?: TeleframeSchemeLoader::defaultSourcesDir());
        $out = (string) ($this->option('out') ?: $packageRoot);

        $engine->force((bool) $this->option('force'));
        $engine->shipNamespaces($this->option('ship') ? $this->shipDial() : null);
        $result = $engine->regenerate($schemas, $out);
        $c = $result['counts'];

        $this->info(sprintf(
            'regenerated: %d files, %d types, %d constructors, %d methods, %d tables, %d deferred FKs (manifest hash %s)',
            $c['files'], $c['types'], $c['constructors'], $c['methods'], $c['tables'], $c['fks'],
            substr($result['manifest']['hash'], 0, 12),
        ));
        if (isset($result['ship'])) {
            $s = $result['ship'];
            $this->info(sprintf(
                'shipped: %d curated migrations to %s (dial: %s)',
                $s['count'],
                $s['dir'],
                implode(', ', $s['namespaces']),
            ));
        }
        return self::SUCCESS;
    }

    /**
     * The curated dial: config('teleframe.ship_namespaces') when a
     * config repository is bound (artisan in a Laravel app), else the
     * canonical default (thin bin/regenerate runner has no config).
     *
     * @return list<string>
     */
    private function shipDial(): array
    {
        $config = $this->getLaravel()->bound('config') ? $this->getLaravel()->make('config') : null;
        $dial = $config instanceof ConfigRepository ? $config->get('teleframe.ship_namespaces') : null;

        return is_array($dial) && $dial !== []
            ? array_values($dial)
            : SchemaRegenerator::DEFAULT_SHIP_NAMESPACES;
    }
}
