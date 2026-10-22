<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Telegram\MultiSession;

use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Telegram\MultiSession\QueryEngine\Policy;
use MeRezaRezaei\Teleframe\Telegram\MultiSession\QueryEngine\QueryContract;
use MeRezaRezaei\Telegram\MultiSession\LoopDetector\LoopDetector;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class BusinessConnectionHandler
{
    private LoggerInterface $logger;

    private BinderRegistry $binderRegistry;

    private LoopDetector $loopDetector;

    private SessionRouter $sessionRouter;

    /** @var array<string, array{id: string, user_id: int, rights: array}> */
    private array $businessConnections = [];

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

    public function handleBusinessConnect(Update $update): void
    {
        $conn = $this->extractBusinessConnection($update);
        if ($conn === null) {
            return;
        }

        $connectionId = $conn['id'];
        $userId = $conn['user_id'];
        $rights = $conn['rights'] ?? [];

        if ($conn['is_enabled'] ?? false) {
            $this->registerBusinessSession($connectionId, $userId, $rights);
            $this->logger->info('Business connection enabled', [
                'connection_id' => $connectionId,
                'user_id' => $userId,
            ]);
        } else {
            $this->unregisterBusinessSession($connectionId);
            $this->logger->info('Business connection disabled', [
                'connection_id' => $connectionId,
            ]);
        }
    }

    public function handleBusinessMessage(Update $update): void
    {
        $msg = $this->extractBusinessMessage($update);
        if ($msg === null) {
            return;
        }

        $connectionId = $msg['connection_id'] ?? '';
        if ($connectionId === '') {
            return;
        }

        $connection = $this->businessConnections[$connectionId] ?? null;
        if ($connection === null) {
            $this->logger->warning('Business message for unknown connection', [
                'connection_id' => $connectionId,
            ]);

            return;
        }

        $actionId = 'business_message';

        if (! $this->loopDetector->shouldExecute($actionId, $connectionId)) {
            $this->logger->debug('Business message blocked by LoopDetector', [
                'action_id' => $actionId,
                'connection_id' => $connectionId,
            ]);

            return;
        }

        $this->loopDetector->recordExecution($actionId, $connectionId);

        $enrichedUpdate = new Update([
            'type' => 'business_message',
            'business_message' => $msg,
        ], $connection['user_id']);

        $this->sessionRouter->route($enrichedUpdate);

        $bindings = $this->binderRegistry->getBindingsFor($enrichedUpdate);
        foreach ($bindings as $binding) {
            $this->executeBinding($binding, $enrichedUpdate);
        }
    }

    public function handleEditedBusinessMessage(Update $update): void
    {
        $this->handleBusinessMessage($update);
    }

    public function handleDeletedBusinessMessages(Update $update): void
    {
        $updateArray = $update->array;
        $connectionId = $this->first($updateArray, ['business_connection_id', 'connection_id']);
        $messageIds = $updateArray['message_ids'] ?? [];

        if ($connectionId === '' || empty($messageIds)) {
            return;
        }

        $this->logger->info('Deleted business messages', [
            'connection_id' => $connectionId,
            'message_ids' => $messageIds,
        ]);
    }

    public function registerBusinessSession(string $connectionId, int $userId, array $rights = []): void
    {
        $this->businessConnections[$connectionId] = [
            'id' => $connectionId,
            'user_id' => $userId,
            'rights' => $rights,
        ];

        $this->sessionRouter->getLoopDetector()->applyPolicy(
            "business:{$connectionId}",
            new Policy(
                cooldownMs: 1000,
                dedupWindowMs: 5000,
                breakCycles: true,
            )
        );
    }

    public function unregisterBusinessSession(string $connectionId): void
    {
        unset($this->businessConnections[$connectionId]);
    }

    public function getBusinessSession(string $connectionId): ?array
    {
        return $this->businessConnections[$connectionId] ?? null;
    }

    public function getAllBusinessSessions(): array
    {
        return $this->businessConnections;
    }

    public function routeToSession(int $userId, array $message, array $context = []): void
    {
        $update = new Update([
            'type' => 'message',
            'message' => $message,
        ], $userId);

        $this->sessionRouter->route($update);
    }

    private function extractBusinessConnection(Update $update): ?array
    {
        $arr = $update->array;

        $conn = $arr['business_connection'] ?? null;
        if ($conn === null) {
            return null;
        }

        if (is_object($conn)) {
            $conn = (array) $conn;
        }

        return [
            'id' => $conn['id'] ?? '',
            'user_id' => $conn['user']['id'] ?? $conn['user_id'] ?? 0,
            'is_enabled' => $conn['is_enabled'] ?? $conn['isEnabled'] ?? false,
            'rights' => $conn['rights'] ?? [],
        ];
    }

    private function extractBusinessMessage(Update $update): ?array
    {
        $arr = $update->array;

        $msg = $arr['business_message'] ?? $arr['edited_business_message'] ?? null;
        if ($msg === null) {
            return null;
        }

        if (is_object($msg)) {
            $msg = (array) $msg;
        }

        if (! isset($msg['connection_id'])) {
            $msg['connection_id'] = $this->first($arr, ['connection_id', 'business_connection_id']) ?? '';
        }

        return $msg;
    }

    private function executeBinding(QueryContract $binding, Update $update): void
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
                $handler->handle($update, $action->payload);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Handler execution failed', [
                'handler' => $handlerClass,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function first(array $arr, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $arr) && $arr[$key] !== null) {
                return $arr[$key];
            }
        }

        return null;
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

class_alias('MeRezaRezaei\Teleframe\Telegram\MultiSession\BusinessConnectionHandler', 'MeRezaRezaei\Telegram\MultiSession\BusinessConnectionHandler');
