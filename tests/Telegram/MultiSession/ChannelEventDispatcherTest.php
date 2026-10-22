<?php

declare(strict_types=1);

namespace MeRezaRezaei\Telegram\MultiSession;

use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Telegram\MultiSession\LoopDetector\LoopDetector;
use MeRezaRezaei\Telegram\MultiSession\QueryEngine\Policy;
use PHPUnit\Framework\TestCase;

class ChannelEventDispatcherTest extends TestCase
{
    public function testShouldDeduplicate(): void
    {
        $dispatcher = new ChannelEventDispatcher();
        
        // First time should not be duplicate
        $this->assertFalse($dispatcher->shouldDeduplicate(123, 'hash_1'));
        
        // Cache the hash
        $dispatcher->cacheContentHash(123, 'hash_1');
        
        // Now it should be duplicate
        $this->assertTrue($dispatcher->shouldDeduplicate(123, 'hash_1'));
        
        // Different channel should not be duplicate
        $this->assertFalse($dispatcher->shouldDeduplicate(456, 'hash_1'));
        
        // Different hash should not be duplicate
        $this->assertFalse($dispatcher->shouldDeduplicate(123, 'hash_2'));
    }

    public function testCacheContentHash(): void
    {
        $dispatcher = new ChannelEventDispatcher();
        
        $dispatcher->cacheContentHash(123, 'hash_1');
        
        // Should be cached
        $this->assertTrue($dispatcher->shouldDeduplicate(123, 'hash_1'));
    }

    public function testLoopDetectorIntegration(): void
    {
        $dispatcher = new ChannelEventDispatcher();
        $loopDetector = $dispatcher->getLoopDetector();
        
        $this->assertInstanceOf(LoopDetector::class, $loopDetector);
    }

    public function testSetLoopDetector(): void
    {
        $dispatcher = new ChannelEventDispatcher();
        $customDetector = new LoopDetector();
        
        $dispatcher->setLoopDetector($customDetector);
        $this->assertSame($customDetector, $dispatcher->getLoopDetector());
    }

    public function testSetBinderRegistry(): void
    {
        $dispatcher = new ChannelEventDispatcher();
        $customRegistry = new BinderRegistry();
        
        $dispatcher->setBinderRegistry($customRegistry);
        $this->assertSame($customRegistry, $dispatcher->getBinderRegistry());
    }

    public function testSetSessionRouter(): void
    {
        $dispatcher = new ChannelEventDispatcher();
        $customRouter = new SessionRouter();
        
        $dispatcher->setSessionRouter($customRouter);
        $this->assertSame($customRouter, $dispatcher->getSessionRouter());
    }

    public function testDispatchChannelPost(): void
    {
        $dispatcher = new ChannelEventDispatcher();
        
        $update = new Update([
            'type' => 'channel_post',
            'channel_post' => [
                'message_id' => 1,
                'chat' => ['id' => -1001234567890, 'type' => 'channel'],
                'date' => time(),
                'text' => 'Test channel post',
            ],
        ], -1001234567890);

        // Should not throw
        $dispatcher->dispatchChannelPost($update);
    }

    public function testDispatchEditedChannelPost(): void
    {
        $dispatcher = new ChannelEventDispatcher();
        
        $update = new Update([
            'type' => 'edited_channel_post',
            'edited_channel_post' => [
                'message_id' => 1,
                'chat' => ['id' => -1001234567890, 'type' => 'channel'],
                'date' => time(),
                'text' => 'Edited channel post',
                'edit_date' => time(),
            ],
        ], -1001234567890);

        // Should not throw
        $dispatcher->dispatchEditedChannelPost($update);
    }
}
