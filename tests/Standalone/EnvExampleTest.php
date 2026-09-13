<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Standalone;

use PHPUnit\Framework\TestCase;

final class EnvExampleTest extends TestCase
{
    public function test_example_exists_with_every_key_and_no_secrets(): void
    {
        $path = dirname(__DIR__, 2).'/examples/.env.example';
        $this->assertFileExists($path);
        $content = (string) file_get_contents($path);
        foreach (['TELEGRAM_API_ID=', 'TELEGRAM_API_HASH=', 'TELEGRAM_DC_ID=', 'TELEFRAME_LIVE=', 'TELEGRAM_USER_SESSION=', 'TELEGRAM_BOT_SESSION=', 'TELEGRAM_BOT_TOKEN=', 'TELEGRAM_WEBHOOK_SECRET=', 'TELEFRAME_LOGGER='] as $key) {
            $this->assertStringContainsString($key, $content, "missing {$key}");
        }
        $this->assertStringNotContainsString('6ad060e', $content);
        $this->assertDoesNotMatchRegularExpression('/[A-Za-z0-9_-]{40,}/', $content);
    }
}
