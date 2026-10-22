<?php

declare(strict_types=1);

namespace MeRezaRezaei\Telegram\MultiSession;

use MeRezaRezaei\Teleframe\Handler\Update;
use PHPUnit\Framework\TestCase;

class SessionRouterIntegrationTest extends TestCase
{
    public function testRouteBlocksDuplicateActions(): void
    {
        $router = new SessionRouter();
        $loopDetector = $router->getLoopDetector();

        // Apply dedup policy
        $policy = new \MeRezaRezaei\Teleframe\Telegram\MultiSession\QueryEngine\Policy(
            cooldownMs: 0,
            dedupWindowMs: 5000,
            maxExecutions: -1,
            breakCycles: true,
            cycleBreakers: []
        );

        // Create a test update
        $update = new Update([
            'type' => 'message',
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 12345, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => 12345, 'type' => 'private'],
                'date' => time(),
                'text' => 'Hello',
            ],
        ], 12345);

        // First route should work
        $router->route($update);

        // Get the action ID
        $actionId = 'message';

        // Apply policy to the action
        $loopDetector->applyPolicy($actionId, $policy);

        // Second route with same action should be blocked by dedup
        $router->route($update);

        // The action should be deduped
        $this->assertTrue($loopDetector->isDeduped($actionId));
    }

    public function testRouteWithBusinessUpdate(): void
    {
        $router = new SessionRouter();

        // Create a business update
        $update = new Update([
            'type' => 'business_message',
            'business_message' => [
                'message_id' => 1,
                'from' => ['id' => 12345, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => 12345, 'type' => 'private'],
                'date' => time(),
                'text' => 'Business Hello',
                'connection_id' => 'conn_123',
            ],
        ], 12345);

        $key = $router->getSessionKey($update);
        $this->assertStringContainsString('business', $key);
        $this->assertStringContainsString('conn_123', $key);
    }

    public function testGetSessionKey(): void
    {
        $router = new SessionRouter();

        $update = new Update([
            'type' => 'message',
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 12345, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => 67890, 'type' => 'private'],
                'date' => time(),
                'text' => 'Hello',
            ],
        ], 12345);

        $key = $router->getSessionKey($update);
        $this->assertStringContainsString('12345', $key);
        $this->assertStringContainsString('67890', $key);
    }

    public function testLoopDetectorInstance(): void
    {
        $router = new SessionRouter();
        $loopDetector = $router->getLoopDetector();

        $this->assertNotNull($loopDetector);
        $this->assertEquals('MeRezaRezaei\Telegram\MultiSession\LoopDetector\LoopDetector', get_class($loopDetector));
    }
}
