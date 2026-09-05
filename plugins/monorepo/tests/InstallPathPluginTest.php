<?php

declare(strict_types=1);

namespace MeRezaRezaei\TeleProtoMonorepo\Tests;

use Composer\Composer;
use Composer\Config;
use Composer\IO\NullIO;
use Composer\Package\RootPackageInterface;
use MeRezaRezaei\TeleProtoMonorepo\InstallPathPlugin;
use PHPUnit\Framework\TestCase;

final class InstallPathPluginTest extends TestCase
{
    public function testActivateRegistersSuggestedInstallPathsForAllFourPackages(): void
    {
        $composer = new Composer();
        $composer->setConfig(new Config());
        $root = $this->createMock(RootPackageInterface::class);
        $composer->setPackage($root);

        $root->expects(self::once())
            ->method('setExtra')
            ->with(self::callback(static function (array $extra): bool {
                $pinned = [
                    'merezarezaei/teleproto-core' => 'vendor/merezarezaei/teleproto-core',
                    'merezarezaei/teleproto-schema' => 'vendor/merezarezaei/teleproto-schema',
                    'merezarezaei/teleproto-laravel' => 'vendor/merezarezaei/teleproto-laravel',
                    'merezarezaei/teleproto-bot' => 'vendor/merezarezaei/teleproto-bot',
                ];
                foreach ($pinned as $name => $path) {
                    if (!in_array($path, $extra['installer-paths'][$name] ?? [], true)) {
                        return false;
                    }
                }
                return true;
            }));

        (new InstallPathPlugin())->activate($composer, new NullIO());
    }

    public function testDeactivateAndUninstallAreNoOps(): void
    {
        $plugin = new InstallPathPlugin();
        $plugin->deactivate(new Composer(), new NullIO());
        $plugin->uninstall(new Composer(), new NullIO());
        self::assertTrue(true, 'no-op lifecycle hooks must not throw');
    }
}