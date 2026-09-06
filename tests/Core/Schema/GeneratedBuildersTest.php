<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Tests\Schema;

use MeRezaRezaei\Teleframe\Core\Methods\Methods;
use MeRezaRezaei\Teleframe\Core\Schema\MethodRegistry;
use PHPUnit\Framework\TestCase;

class GeneratedBuildersTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        MethodRegistry::load();
        require_once dirname(__DIR__, 3) . '/src/Core/Methods/Generated/Messages.php';
    }

    public function testSendMessageBuilderProducesExactRequestArray(): void
    {
        $req = Methods::messages()->sendMessage()
            ->peer(['_' => 'inputPeerSelf'])
            ->message('hello')
            ->randomId(12345)
            ->toRequest();
        $this->assertSame('messages.sendMessage', $req['_']);
        $this->assertSame(['_' => 'inputPeerSelf'], $req['peer']);
        $this->assertSame('hello', $req['message']);
        $this->assertSame(12345, $req['random_id']); // snake_case on the wire
    }

    public function testBuilderValidatesRequiredParams(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('message');
        Methods::messages()->sendMessage()->peer(['_' => 'inputPeerSelf'])->toRequest();
    }
}
