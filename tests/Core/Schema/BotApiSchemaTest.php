<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Tests\Schema;

use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;
use PHPUnit\Framework\TestCase;

class BotApiSchemaTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function artifact(): array
    {
        return (array) json_decode((string) file_get_contents(SchemaArtifacts::path('methods-botapi.json')), true);
    }

    public function testEnvelopeAndSpotEntries(): void
    {
        $a = self::artifact();
        $this->assertSame('bot-http', $a['api']);
        $this->assertGreaterThan(150, count($a['methods']));
        $send = $a['methods']['sendMessage'];
        $this->assertSame('https://core.telegram.org/bots/api#sendmessage', $send['docs']);
        $this->assertContains('chat_id', $send['required']);
        $this->assertContains('text', $send['required']);
        $this->assertArrayHasKey('getMe', $a['methods']);
        // per-param descriptions carried through from api.json
        $chatId = array_values(array_filter($send['params'], fn (array $p) => $p['name'] === 'chat_id'))[0];
        $this->assertNotSame('', $chatId['description'] ?? '');
    }
}
