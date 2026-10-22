<?php

declare(strict_types=1);

namespace MeRezaRezaei\Telegram\MultiSession;

use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Telegram\MultiSession\LoopDetector\LoopDetector;
use MeRezaRezaei\Telegram\MultiSession\QueryEngine\Policy;
use PHPUnit\Framework\TestCase;

class BusinessConnectionHandlerTest extends TestCase
{
    public function testRegisterAndUnregisterBusinessSession(): void
    {
        $handler = new BusinessConnectionHandler();
        
        $handler->registerBusinessSession('conn_123', 12345, ['can_reply' => true]);
        
        $session = $handler->getBusinessSession('conn_123');
        $this->assertNotNull($session);
        $this->assertEquals('conn_123', $session['id']);
        $this->assertEquals(12345, $session['user_id']);
        $this->assertEquals(['can_reply' => true], $session['rights']);
        
        $handler->unregisterBusinessSession('conn_123');
        $this->assertNull($handler->getBusinessSession('conn_123'));
    }

    public function testGetAllBusinessSessions(): void
    {
        $handler = new BusinessConnectionHandler();
        
        $handler->registerBusinessSession('conn_1', 1, []);
        $handler->registerBusinessSession('conn_2', 2, []);
        
        $sessions = $handler->getAllBusinessSessions();
        $this->assertCount(2, $sessions);
        $this->assertArrayHasKey('conn_1', $sessions);
        $this->assertArrayHasKey('conn_2', $sessions);
    }

    public function testHandleBusinessConnect(): void
    {
        $handler = new BusinessConnectionHandler();
        
        $update = new Update([
            'type' => 'business_connection',
            'business_connection' => [
                'id' => 'conn_123',
                'user' => ['id' => 12345],
                'is_enabled' => true,
                'rights' => ['can_reply' => true],
            ],
        ], 12345);

        $handler->handleBusinessConnect($update);
        
        $session = $handler->getBusinessSession('conn_123');
        $this->assertNotNull($session);
        $this->assertEquals(12345, $session['user_id']);
    }

    public function testHandleBusinessConnectDisabled(): void
    {
        $handler = new BusinessConnectionHandler();
        
        $handler->registerBusinessSession('conn_123', 12345, []);
        
        $update = new Update([
            'type' => 'business_connection',
            'business_connection' => [
                'id' => 'conn_123',
                'user' => ['id' => 12345],
                'is_enabled' => false,
            ],
        ], 12345);

        $handler->handleBusinessConnect($update);
        
        $this->assertNull($handler->getBusinessSession('conn_123'));
    }

    public function testLoopDetectorIntegration(): void
    {
        $handler = new BusinessConnectionHandler();
        $loopDetector = $handler->getLoopDetector();
        
        $this->assertInstanceOf(LoopDetector::class, $loopDetector);
    }

    public function testSetLoopDetector(): void
    {
        $handler = new BusinessConnectionHandler();
        $customDetector = new LoopDetector();
        
        $handler->setLoopDetector($customDetector);
        $this->assertSame($customDetector, $handler->getLoopDetector());
    }

    public function testSetBinderRegistry(): void
    {
        $handler = new BusinessConnectionHandler();
        $customRegistry = new BinderRegistry();
        
        $handler->setBinderRegistry($customRegistry);
        $this->assertSame($customRegistry, $handler->getBinderRegistry());
    }

    public function testSetSessionRouter(): void
    {
        $handler = new BusinessConnectionHandler();
        $customRouter = new SessionRouter();
        
        $handler->setSessionRouter($customRouter);
        $this->assertSame($customRouter, $handler->getSessionRouter());
    }
}
