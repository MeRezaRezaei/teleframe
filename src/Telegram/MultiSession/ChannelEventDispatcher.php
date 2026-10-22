<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Telegram\MultiSession;

use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Telegram\MultiSession\QueryEngine\Action;
use MeRezaRezaei\Teleframe\Telegram\MultiSession\QueryEngine\Policy;
use MeRezaRezaei\Teleframe\Telegram\MultiSession\QueryEngine\Query;
use MeRezaRezaei\Teleframe\Telegram\MultiSession\QueryEngine\QueryContract;
use MeRezaRezaei\Telegram\MultiSession\LoopDetector\LoopDetector;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class ChannelEventDispatcher
{
    private LoggerInterface $logger;

    private BinderRegistry $binderRegistry;

    private LoopDetector $loopDetector;

    private SessionRouter $sessionRouter;

    /** @var array<string, int> */
    private array $contentHashCache = [];

    public function __construct(
        ?LoggerInterface $logger = null,
        ?BinderRegistry $binderRegistry = null,
        ?LoopDetector $loopDetector = null,
        ?SessionRouter $sessionRouter = null,
    ) {
        $this->logger = $logger ?? new NullLogger;
        $this->binderRegistry = $binderRegistry ?? new BinderRegistry;
        $this->loopDetector = $loopDetector ?? new LoopDetector;
        $this->sessionRouter = $sessionRouter ?? new SessionRouter;
    }

    public function dispatchChannelPost(Update $update): void
    {
        $post = $this->extractChannelPost($update);
        if ($post === null) {
            return;
        }

        $channelId = $post['chat']['id'] ?? 0;
        if ($channelId === 0) {
            return;
        }

        $contentHash = $this->computeContentHash($post);

        if ($this->shouldDeduplicate($channelId, $contentHash)) {
            $this->logger->debug('Channel post deduplicated', [
                'channel_id' => $channelId,
                'content_hash' => $contentHash,
            ]);

            return;
        }

        $this->cacheContentHash($channelId, $contentHash);

        $actionId = "channel_post:{$channelId}";

        if (! $this->loopDetector->shouldExecute($actionId, "channel:{$channelId}")) {
            $this->logger->debug('Channel post blocked by LoopDetector', [
                'action_id' => $actionId,
                'channel_id' => $channelId,
            ]);

            return;
        }

        $this->loopDetector->recordExecution($actionId, "channel:{$channelId}");

        // Create an update object for the channel post to get bindings
        $bindingUpdate = new Update([
            'type' => 'channel_post',
            'channel_post' => $post,
        ], $channelId);

        $bindings = $this->binderRegistry->getBindingsFor($bindingUpdate);

        foreach ($bindings as $binding) {
            $policy = $binding->getPolicy();
            if ($policy->breakCycles && $this->loopDetector->wouldCreateCycle($actionId, $binding->getAction()->handlerClass)) {
                $this->loopDetector->applyPolicy($actionId, $policy);
                $this->logger->warning('Channel post cycle detected, applying cooldown', [
                    'action_id' => $actionId,
                    'channel_id' => $channelId,
                ]);

                continue;
            }

            $this->executeBinding($binding, $update, $post);
        }

        $this->logger->info('Channel post dispatched', [
            'channel_id' => $channelId,
            'bindings_executed' => count($bindings),
        ]);
    }

    public function dispatchEditedChannelPost(Update $update): void
    {
        $this->dispatchChannelPost($update);
    }

    public function dispatchChannelPostWithAction(Update $update, string $actionId, array $payload = []): void
    {
        $post = $this->extractChannelPost($update);
        if ($post === null) {
            return;
        }

        $channelId = $post['chat']['id'] ?? 0;

        $binding = new class(Query::channelEvent($channelId, 'new_post'), new Action($actionId, $payload), new Policy(cooldownMs: 1000, dedupWindowMs: 5000, breakCycles: true), '1.0.0') implements QueryContract
        {
            public function __construct(
                private Query $query,
                private Action $action,
                private Policy $policy,
                private string $version
            ) {}

            public function getQuery(): Query
            {
                return $this->query;
            }

            public function getAction(): Action
            {
                return $this->action;
            }

            public function getPolicy(): Policy
            {
                return $this->policy;
            }

            public function getVersion(): string
            {
                return $this->version;
            }
        };

        $this->executeBinding($binding, $update, $post);
    }

    public function shouldDeduplicate(int $channelId, string $contentHash): bool
    {
        $key = "channel:{$channelId}:{$contentHash}";

        if (isset($this->contentHashCache[$key])) {
            $age = time() - $this->contentHashCache[$key];
            if ($age < 3600) {
                return true;
            }
        }

        return false;
    }

    public function cacheContentHash(int $channelId, string $contentHash): void
    {
        $key = "channel:{$channelId}:{$contentHash}";
        $this->contentHashCache[$key] = time();

        if (count($this->contentHashCache) > 10000) {
            $this->cleanCache();
        }
    }

    private function cleanCache(): void
    {
        $now = time();
        $this->contentHashCache = array_filter(
            $this->contentHashCache,
            fn (int $timestamp) => ($now - $timestamp) < 3600
        );
    }

    private function computeContentHash(array $post): string
    {
        $content = '';

        if (isset($post['text'])) {
            $content .= $post['text'];
        }
        if (isset($post['caption'])) {
            $content .= $post['caption'];
        }
        if (isset($post['media_group_id'])) {
            $content .= $post['media_group_id'];
        }

        if ($content === '') {
            $content = json_encode($post);
        }

        return hash('xxh3', $content, false, ['length' => 16]);
    }

    private function extractChannelPost(Update $update): ?array
    {
        $arr = $update->array;

        $post = $arr['channel_post'] ?? $arr['edited_channel_post'] ?? null;
        if ($post === null) {
            return null;
        }

        if (is_object($post)) {
            $post = (array) $post;
        }

        return $post;
    }

    private function executeBinding(QueryContract $binding, Update $update, array $post): void
    {
        $action = $binding->getAction();
        $handlerClass = $action->handlerClass;

        if (! class_exists($handlerClass)) {
            $this->logger->error('Handler class not found', ['class' => $handlerClass]);

            return;
        }

        try {
            $handler = new $handlerClass;
            if (method_exists($handler, 'handle')) {
                $handler->handle($update, array_merge($action->payload, ['channel_post' => $post]));
            }
        } catch (\Throwable $e) {
            $this->logger->error('Channel handler execution failed', [
                'handler' => $handlerClass,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getLoopDetector(): LoopDetector
    {
        return $this->loopDetector;
    }

    public function setLoopDetector(LoopDetector $loopDetector): void
    {
        $this->loopDetector = $loopDetector;
    }

    public function getBinderRegistry(): BinderRegistry
    {
        return $this->binderRegistry;
    }

    public function setBinderRegistry(BinderRegistry $binderRegistry): void
    {
        $this->binderRegistry = $binderRegistry;
    }

    public function getSessionRouter(): SessionRouter
    {
        return $this->sessionRouter;
    }

    public function setSessionRouter(SessionRouter $sessionRouter): void
    {
        $this->sessionRouter = $sessionRouter;
    }
}

class_alias('MeRezaRezaei\Teleframe\Telegram\MultiSession\ChannelEventDispatcher', 'MeRezaRezaei\Telegram\MultiSession\ChannelEventDispatcher');
