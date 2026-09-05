<?php

declare(strict_types=1);

namespace MeRezaRezaei\TeleProtoMonorepo;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * Root-only dev plugin: declares explicit install paths for the four monorepo
 * packages so vendor/ layout is deterministic across composer versions.
 *
 * Writes composer's canonical "installer-paths" extra shape:
 *     ['installer-paths' => ['merezarezaei/teleproto-core' => ['vendor/merezarezaei/teleproto-core', ...], ...]]
 * Composer core honors this shape for path repositories without needing
 * composer/installers, which we deliberately avoid as a runtime dependency.
 */
final class InstallPathPlugin implements PluginInterface, EventSubscriberInterface
{
    private const PINNED = [
        'merezarezaei/teleproto-core' => 'vendor/merezarezaei/teleproto-core',
        'merezarezaei/teleproto-schema' => 'vendor/merezarezaei/teleproto-schema',
        'merezarezaei/teleproto-laravel' => 'vendor/merezarezaei/teleproto-laravel',
        'merezarezaei/teleproto-bot' => 'vendor/merezarezaei/teleproto-bot',
    ];

    public static function getSubscribedEvents(): array
    {
        // Pinning happens at activate time; no post-install events needed.
        return [];
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        $root = $composer->getPackage();
        $extra = $root->getExtra();
        $paths = $extra['installer-paths'] ?? [];
        foreach (self::PINNED as $name => $path) {
            $paths[$name] = array_values(array_unique([...($paths[$name] ?? []), $path]));
        }
        $extra['installer-paths'] = $paths;
        $root->setExtra($extra);
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // no-op
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // no-op
    }
}