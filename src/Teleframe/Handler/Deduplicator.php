<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler;

use MeRezaRezaei\Telegram\MultiSession\LoopDetector\LoopDetector;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;

/**
 * Cross-session deduplication for the enhanced singleton handler.
 *
 * Multiple sessions of the SAME Telegram account (and bot assistants that
 * mirror a user account's updates) can deliver the same logical update to
 * the handler pipeline more than once. This class collapses those into a
 * single execution using a Redis-backed (PSR-16) store keyed on
 * `account_id + update_id + update_hash`, with a configurable TTL window
 * and scope (account-level vs bot-assistant-level).
 *
 * Plain-PHP constructible: the cache defaults to the in-memory PSR-16 store
 * when no host binding is supplied, so the standalone smoke and tests can
 * exercise it without a Redis service.
 */
final class Deduplicator
{
    public const KEY = 'tg:bus:dedup';

    public const SCOPE_ACCOUNT = 'account';

    public const SCOPE_BOT_ASSISTANT = 'bot-assistant';

    private CacheInterface $cache;

    private int $ttl;

    private string $scope;

    private string $prefix;

    private ?LoopDetector $loopDetector;

    /** @var callable|null */
    private mixed $scopeResolver;

    private LoggerInterface $logger;

    public function __construct(
        mixed $cache = null,
        int $ttl = 60,
        string $scope = self::SCOPE_ACCOUNT,
        ?LoopDetector $loopDetector = null,
        string $prefix = self::KEY,
        mixed $scopeResolver = null,
        ?LoggerInterface $logger = null,
        mixed $store = null,
        mixed $redis = null,
        ?int $dedupTtl = null,
        ?string $dedupScope = null,
        ?int $deduplicationTtl = null,
        ?string $deduplicationScope = null,
        ?int $ttlSeconds = null,
        ?int $window = null,
        ?string $level = null,
        ?string $keyPrefix = null,
        mixed $loop = null,
        mixed $resolver = null,
    ) {
        $resolvedCache = $cache ?? $store ?? $redis;
        if (! $resolvedCache instanceof CacheInterface) {
            $resolvedCache = new InMemoryCache;
        }
        $this->cache = $resolvedCache;

        $resolvedTtl = $dedupTtl ?? $deduplicationTtl ?? $ttlSeconds ?? $window ?? $ttl;
        $this->ttl = $resolvedTtl > 0 ? $resolvedTtl : 60;

        $resolvedScope = $dedupScope ?? $deduplicationScope ?? $level ?? $scope;
        $this->scope = $resolvedScope !== '' ? $resolvedScope : self::SCOPE_ACCOUNT;

        $resolvedLoop = $loopDetector ?? $loop;
        $this->loopDetector = $resolvedLoop instanceof LoopDetector ? $resolvedLoop : null;

        $resolvedPrefix = $keyPrefix ?? $prefix;
        $this->prefix = $resolvedPrefix !== '' ? $resolvedPrefix : self::KEY;

        $resolvedResolver = $scopeResolver ?? $resolver;
        $this->scopeResolver = is_callable($resolvedResolver) ? $resolvedResolver : null;

        $this->logger = $logger instanceof LoggerInterface ? $logger : new NullLogger;
    }

    /**
     * Canonical deduplication key: account_id + update_id + update_hash.
     * Session ids are deliberately NOT part of the key so that the same
     * logical update arriving through different sessions collapses.
     */
    public function key(Update $update, ?int $sessionId = null): string
    {
        $scopeId = $this->resolveScopeId($update);
        $updateId = $this->extractUpdateId($update);
        $hash = $update->updateId();

        return $this->prefix.':'.$this->scope.':'.$scopeId.':'.$updateId.':'.$hash;
    }

    public function dedupKey(Update $update, ?int $sessionId = null): string
    {
        return $this->key($update, $sessionId);
    }

    public function deduplicationKey(Update $update, ?int $sessionId = null): string
    {
        return $this->key($update, $sessionId);
    }

    public function makeKey(Update $update, ?int $sessionId = null): string
    {
        return $this->key($update, $sessionId);
    }

    /**
     * Combined check-and-mark: returns true when the update has already been
     * seen within the TTL window (i.e. should be skipped). The first call
     * for a given key returns false and records it.
     */
    public function isDuplicate(Update $update, ?int $sessionId = null): bool
    {
        // The event path rides stored-truth idempotency (ReplayDedup and the
        // dispatcher's forward guard both skip it for exactly this reason):
        // its array is a constructor-only projection, so a key built on it
        // would be a per-(account, constructor) constant and mass-drop every
        // later event of that kind. Decline to dedupe rather than invent one.
        if ($update->source === 'event') {
            return false;
        }

        $key = $this->key($update, $sessionId);

        if ($this->cache->has($key)) {
            return true;
        }

        if ($this->loopDetector !== null) {
            $triggeredBy = $this->scope === self::SCOPE_BOT_ASSISTANT ? 'bot-assistant' : null;
            $this->loopDetector->recordExecution($key, $triggeredBy);
            if (! $this->loopDetector->shouldExecute($key, $triggeredBy)) {
                $this->cache->set($key, true, $this->ttl);

                return true;
            }
        }

        return ! $this->cache->set($key, true, $this->ttl);
    }

    public function check(Update $update, ?int $sessionId = null): bool
    {
        return $this->isDuplicate($update, $sessionId);
    }

    public function deduplicate(Update $update, ?int $sessionId = null): bool
    {
        return $this->isDuplicate($update, $sessionId);
    }

    public function isDuplicateUpdate(Update $update, ?int $sessionId = null): bool
    {
        return $this->isDuplicate($update, $sessionId);
    }

    /**
     * Pure lookup: does not record. Returns true when the key is present.
     */
    public function isSeen(Update $update, ?int $sessionId = null): bool
    {
        if ($update->source === 'event') {
            return false;
        }

        return $this->cache->has($this->key($update, $sessionId));
    }

    public function seen(Update $update, ?int $sessionId = null): bool
    {
        return $this->isSeen($update, $sessionId);
    }

    public function has(Update $update, ?int $sessionId = null): bool
    {
        return $this->isSeen($update, $sessionId);
    }

    /**
     * Record the update as seen without consulting the store first.
     */
    public function markSeen(Update $update, ?int $sessionId = null): bool
    {
        return $this->cache->set($this->key($update, $sessionId), true, $this->ttl);
    }

    public function mark(Update $update, ?int $sessionId = null): bool
    {
        return $this->markSeen($update, $sessionId);
    }

    public function remember(Update $update, ?int $sessionId = null): bool
    {
        return $this->markSeen($update, $sessionId);
    }

    public function record(Update $update, ?int $sessionId = null): bool
    {
        return $this->markSeen($update, $sessionId);
    }

    /**
     * Inverse of isDuplicate (combined): true when the update should be
     * processed. Records the key on the first call.
     */
    public function shouldProcess(Update $update, ?int $sessionId = null): bool
    {
        return ! $this->isDuplicate($update, $sessionId);
    }

    public function shouldExecute(Update $update, ?int $sessionId = null): bool
    {
        return $this->shouldProcess($update, $sessionId);
    }

    public function allow(Update $update, ?int $sessionId = null): bool
    {
        return $this->shouldProcess($update, $sessionId);
    }

    public function getLoopDetector(): ?LoopDetector
    {
        return $this->loopDetector;
    }

    public function setLoopDetector(?LoopDetector $loopDetector): void
    {
        $this->loopDetector = $loopDetector;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    private function resolveScopeId(Update $update): int|string
    {
        $accountId = $update->accountId;

        if ($this->scope === self::SCOPE_BOT_ASSISTANT && $this->scopeResolver !== null) {
            try {
                $resolved = ($this->scopeResolver)($accountId, $update);
                if (is_int($resolved) || is_string($resolved)) {
                    return $resolved;
                }
            } catch (\Throwable $e) {
                $this->logger->warning('Deduplicator scope resolver failed', [
                    'account' => $accountId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $accountId;
    }

    private function extractUpdateId(Update $update): string
    {
        foreach (['update_id', 'updateId', 'id'] as $key) {
            if (array_key_exists($key, $update->array)) {
                $value = $update->array[$key];
                if (is_scalar($value) && $value !== '') {
                    return (string) $value;
                }
            }
        }

        // Nested common shapes.
        foreach (['message', 'edited_message', 'callback_query'] as $nested) {
            if (isset($update->array[$nested]) && is_array($update->array[$nested])) {
                foreach (['update_id', 'id'] as $key) {
                    if (isset($update->array[$nested][$key]) && is_scalar($update->array[$nested][$key])) {
                        return (string) $update->array[$nested][$key];
                    }
                }
            }
        }

        return $update->updateId();
    }
}

class_alias('MeRezaRezaei\Teleframe\Handler\Deduplicator', 'MeRezaRezaei\Telegram\MultiSession\Deduplicator');
