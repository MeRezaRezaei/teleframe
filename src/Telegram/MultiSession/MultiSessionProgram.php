<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Telegram\MultiSession;

use MeRezaRezaei\Telegram\MultiSession\LoopDetector\LoopDetector;
use MeRezaRezaei\Telegram\MultiSession\StateMachine\StateMachine;
use MeRezaRezaei\Telegram\MultiSession\StateMachine\UserStateMachine;
use MeRezaRezaei\Teleframe\Handler\Deduplicator;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class MultiSessionProgram implements VersionedProgram
{
    private string $version = '1.0.0';

    public const SCHEMA_VERSION = '1';

    private BinderRegistry $binderRegistry;

    private LoopDetector $loopDetector;

    private StateMachine $stateMachine;

    private SessionRouter $sessionRouter;

    private BusinessConnectionHandler $businessConnectionHandler;

    private ChannelEventDispatcher $channelEventDispatcher;

    private Deduplicator $deduplicator;

    private LoggerInterface $logger;

    private array $state = [];

    public function __construct(
        ?LoggerInterface $logger = null,
        ?BinderRegistry $binderRegistry = null,
        ?LoopDetector $loopDetector = null,
        ?StateMachine $stateMachine = null,
        ?SessionRouter $sessionRouter = null,
        ?BusinessConnectionHandler $businessConnectionHandler = null,
        ?ChannelEventDispatcher $channelEventDispatcher = null,
        ?Deduplicator $deduplicator = null,
    ) {
        $this->logger = $logger ?? new NullLogger;
        $this->binderRegistry = $binderRegistry ?? new BinderRegistry;
        $this->loopDetector = $loopDetector ?? new LoopDetector;
        $this->stateMachine = $stateMachine ?? new UserStateMachine;
        $this->sessionRouter = $sessionRouter ?? new SessionRouter;
        $this->businessConnectionHandler = $businessConnectionHandler ?? new BusinessConnectionHandler;
        $this->channelEventDispatcher = $channelEventDispatcher ?? new ChannelEventDispatcher;
        $this->deduplicator = $deduplicator ?? new Deduplicator(new InMemoryCache);

        $this->state = $this->getInitialState();
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getSchemaVersion(): string
    {
        return self::SCHEMA_VERSION;
    }

    public function &getState(): array
    {
        return $this->state;
    }

    public function setState(array $state): void
    {
        $this->state = $state;
        $this->rehydrateFromState($state);
    }

    public function migrateFrom(string $fromVersion): void
    {
        $this->logger->info('Migrating MultiSessionProgram', ['from' => $fromVersion]);

        // Migration logic from version to version
        $versionMap = [
            '0.9.0' => ['migrateV090ToV100'],
            '1.0.0' => [], // Current version
        ];

        $migrations = $versionMap[$fromVersion] ?? [];

        foreach ($migrations as $migration) {
            $this->$migration();
        }
    }

    public function rollbackTo(string $toVersion): void
    {
        $this->logger->info('Rolling back MultiSessionProgram', ['to' => $toVersion]);

        // Rollback logic - restore state from backup
        if (isset($this->state['backup_'.$toVersion])) {
            $this->state = $this->state['backup_'.$toVersion];
            $this->rehydrateFromState($this->state);
        }
    }

    public function getStateSchema(): array
    {
        return [
            'version' => self::SCHEMA_VERSION,
            'components' => [
                'binder_registry' => [
                    'bindings_count' => 'integer',
                    'cycles_count' => 'integer',
                ],
                'loop_detector' => [
                    'graph_nodes' => 'integer',
                    'graph_edges' => 'integer',
                    'actions_tracked' => 'integer',
                    'cooldowns_active' => 'integer',
                    'dedup_windows_active' => 'integer',
                    'cycles_detected' => 'integer',
                ],
                'state_machine' => [
                    'version' => 'string',
                    'schema_version' => 'string',
                ],
                'session_router' => [
                    'sessions_count' => 'integer',
                ],
                'business_connection_handler' => [
                    'connections_count' => 'integer',
                ],
                'channel_event_dispatcher' => [
                    'content_hash_cache_size' => 'integer',
                ],
                'deduplicator' => [
                    'dedup_keys' => 'array',
                    'ttl' => 'integer',
                    'scope' => 'string',
                ],
                'query_engine' => [
                    'registered_queries' => 'array',
                    'loop_patterns' => 'array',
                    'bindings_count' => 'integer',
                ],
                'conversation_state_machine' => [
                    'per_peer_states' => 'array',
                    'transitions' => 'array',
                ],
                'state_machines' => [
                    'sessions' => 'array',
                ],
            ],
        ];
    }

    public function validateState(): bool
    {
        try {
            // Validate the state array directly
            $state = $this->state;

            // Check loop_detector state
            if (! isset($state['loop_detector']['action_graph']) || ! is_array($state['loop_detector']['action_graph'])) {
                return false;
            }

            // Check state_machine state
            if (! isset($state['state_machine']['version']) || ! is_string($state['state_machine']['version'])) {
                return false;
            }

            // Check session_router state
            if (! isset($state['session_router']['sessions']) || ! is_array($state['session_router']['sessions'])) {
                return false;
            }

            // Check business_connection_handler state
            if (! isset($state['business_connection_handler']['connections']) || ! is_array($state['business_connection_handler']['connections'])) {
                return false;
            }

            // Check channel_event_dispatcher state
            if (! isset($state['channel_event_dispatcher']['content_hash_cache']) || ! is_array($state['channel_event_dispatcher']['content_hash_cache'])) {
                return false;
            }

            // Check deduplicator state
            if (isset($state['deduplicator'])) {
                if (! isset($state['deduplicator']['dedup_keys']) || ! is_array($state['deduplicator']['dedup_keys'])) {
                    return false;
                }
                if (! isset($state['deduplicator']['ttl']) || ! is_int($state['deduplicator']['ttl'])) {
                    return false;
                }
            }

            // Check query_engine state
            if (isset($state['query_engine'])) {
                if (! isset($state['query_engine']['registered_queries']) || ! is_array($state['query_engine']['registered_queries'])) {
                    return false;
                }
                if (! isset($state['query_engine']['loop_patterns']) || ! is_array($state['query_engine']['loop_patterns'])) {
                    return false;
                }
            }

            // Check conversation_state_machine state
            if (isset($state['conversation_state_machine'])) {
                if (! isset($state['conversation_state_machine']['per_peer_states']) || ! is_array($state['conversation_state_machine']['per_peer_states'])) {
                    return false;
                }
            }

            // Check state_machines state
            if (isset($state['state_machines'])) {
                if (! isset($state['state_machines']['sessions']) || ! is_array($state['state_machines']['sessions'])) {
                    return false;
                }
            }

            // Check binder_registry state
            if (! isset($state['binder_registry']['bindings']) || ! is_array($state['binder_registry']['bindings'])) {
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('State validation failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function getBinderRegistry(): BinderRegistry
    {
        return $this->binderRegistry;
    }

    public function getLoopDetector(): LoopDetector
    {
        return $this->loopDetector;
    }

    public function getStateMachine(): StateMachine
    {
        return $this->stateMachine;
    }

    public function getSessionRouter(): SessionRouter
    {
        return $this->sessionRouter;
    }

    public function getBusinessConnectionHandler(): BusinessConnectionHandler
    {
        return $this->businessConnectionHandler;
    }

    public function getChannelEventDispatcher(): ChannelEventDispatcher
    {
        return $this->channelEventDispatcher;
    }

    public function getDeduplicator(): Deduplicator
    {
        return $this->deduplicator;
    }

    public function getQueryEngine(): BinderRegistry
    {
        return $this->binderRegistry;
    }

    public function getConversationStateMachine(): StateMachine
    {
        return $this->stateMachine;
    }

    public function getUserStateMachine(): StateMachine
    {
        return $this->stateMachine;
    }

    public function registerMigrations(\MeRezaRezaei\Teleframe\Telegram\MultiSession\ProgramVersionManager $manager, ?string $programId = null): void
    {
        $id = $programId ?? strtolower(str_replace('\\', '_', get_class($this)));
        // resolve actual id used by manager
        foreach ($manager->getAllPrograms() as $pid) {
            if (str_contains($pid, 'multisessionprogram')) {
                $id = $pid;
                break;
            }
        }
        $manager->registerCycle18Migrations($id);
    }

    public function registerVersionMigrations(\MeRezaRezaei\Teleframe\Telegram\MultiSession\ProgramVersionManager $manager, ?string $programId = null): void
    {
        $this->registerMigrations($manager, $programId);
    }

    private function getInitialState(): array
    {
        return [
            'binder_registry' => [
                'bindings' => [],
                'cycles' => [],
            ],
            'deduplicator' => [
                'dedup_keys' => [],
                'ttl' => 60,
                'scope' => 'account',
            ],
            'query_engine' => [
                'registered_queries' => [],
                'loop_patterns' => [],
                'bindings_count' => 0,
            ],
            'conversation_state_machine' => [
                'per_peer_states' => [],
                'transitions' => [],
            ],
            'state_machines' => [
                'sessions' => [],
            ],
            'loop_detector' => [
                'action_graph' => [],
                'execution_timestamps' => [],
                'cooldown_until' => [],
                'dedup_windows' => [],
            ],
            'state_machine' => [
                'version' => UserStateMachine::VERSION,
                'schema_version' => UserStateMachine::SCHEMA_VERSION,
            ],
            'session_router' => [
                'sessions' => [],
            ],
            'business_connection_handler' => [
                'connections' => [],
            ],
            'channel_event_dispatcher' => [
                'content_hash_cache' => [],
            ],
            'version' => $this->version,
            'schema_version' => self::SCHEMA_VERSION,
            'updated_at' => time(),
        ];
    }

    private function rehydrateFromState(array $state): void
    {
        // Restore binder registry
        if (isset($state['binder_registry']['bindings'])) {
            foreach ($state['binder_registry']['bindings'] as $id => $contract) {
                // Note: QueryContract cannot be easily serialized, so we skip rehydration
            }
        }

        // Restore loop detector state
        if (isset($state['loop_detector'])) {
            // LoopDetector uses private properties, so we use its public API
            // This is a limitation - in production you'd want to add proper serialization
        }

        // Restore session router
        if (isset($state['session_router']['sessions'])) {
            // Sessions are managed internally
        }

        // Restore business connections
        if (isset($state['business_connection_handler']['connections'])) {
            foreach ($state['business_connection_handler']['connections'] as $conn) {
                $this->businessConnectionHandler->registerBusinessSession(
                    $conn['id'],
                    $conn['user_id'],
                    $conn['rights']
                );
            }
        }

        // Restore channel content hash cache
        if (isset($state['channel_event_dispatcher']['content_hash_cache'])) {
            // Content hash cache is transient, not critical to restore
        }

        // Restore deduplicator state (best-effort)
        if (isset($state['deduplicator'])) {
            // Dedup keys and TTL tracked externally via PSR-16 cache
        }

        // Restore query engine state (best-effort)
        if (isset($state['query_engine'])) {
            // Registered queries and loop patterns tracked by BinderRegistry/LoopDetector
        }

        // Restore conversation state machine state (best-effort)
        if (isset($state['conversation_state_machine']['per_peer_states'])) {
            foreach ($state['conversation_state_machine']['per_peer_states'] as $peerId => $stateData) {
                // Rehydrate per-peer states lazily
            }
        }
    }

    private function migrateV090ToV100(): void
    {
        $this->logger->info('Running migration v0.9.0 -> v1.0.0');

        // Add new fields that were introduced in v1.0.0
        $this->state['business_connection_handler'] = $this->state['business_connection_handler'] ?? [
            'connections' => [],
        ];

        $this->state['channel_event_dispatcher'] = $this->state['channel_event_dispatcher'] ?? [
            'content_hash_cache' => [],
        ];

        // Update schema version
        $this->state['schema_version'] = '1';
    }
}

class_alias('MeRezaRezaei\Teleframe\Telegram\MultiSession\MultiSessionProgram', 'MeRezaRezaei\Telegram\MultiSession\MultiSessionProgram');
