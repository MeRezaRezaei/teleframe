<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Telegram\MultiSession;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class ProgramVersionManager
{
    /** @var array<string, VersionedProgram> */
    private array $programs = [];

    /** @var array<string, array{version: string, schema_version: string, state: array}> */
    private array $programStates = [];

    private LoggerInterface $logger;

    /** @var array<string, array<string, callable(array): array>> */
    private array $migrations = [];

    /** @var array<string, array<string, callable(array): array>> */
    private array $rollbackMigrations = [];

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger;
    }

    public function register(VersionedProgram $program): void
    {
        $id = $this->getProgramId($program);
        $this->programs[$id] = $program;

        $this->programStates[$id] = [
            'version' => $program->getVersion(),
            'schema_version' => $program->getSchemaVersion(),
            'state' => $program->getState(),
        ];

        $this->logger->info('Program registered', [
            'program_id' => $id,
            'version' => $program->getVersion(),
            'schema_version' => $program->getSchemaVersion(),
        ]);
    }

    public function unregister(string $programId): void
    {
        unset($this->programs[$programId]);
        unset($this->programStates[$programId]);
    }

    public function getProgram(string $programId): ?VersionedProgram
    {
        return $this->programs[$programId] ?? null;
    }

    public function getProgramState(string $programId): ?array
    {
        $program = $this->programs[$programId] ?? null;
        if ($program !== null) {
            return [
                'version' => $program->getVersion(),
                'schema_version' => $program->getSchemaVersion(),
                'state' => $program->getState(),
            ];
        }

        return $this->programStates[$programId] ?? null;
    }

    public function getAllPrograms(): array
    {
        return array_keys($this->programs);
    }

    public function addMigration(string $programId, string $fromVersion, string $toVersion, callable $migration): void
    {
        if (! isset($this->migrations[$programId])) {
            $this->migrations[$programId] = [];
        }
        $key = "{$fromVersion}->{$toVersion}";
        $this->migrations[$programId][$key] = $migration;

        $this->logger->debug('Migration added', [
            'program_id' => $programId,
            'from' => $fromVersion,
            'to' => $toVersion,
        ]);
    }

    public function addRollbackMigration(string $programId, string $fromVersion, string $toVersion, callable $migration): void
    {
        if (! isset($this->rollbackMigrations[$programId])) {
            $this->rollbackMigrations[$programId] = [];
        }
        $key = "{$fromVersion}->{$toVersion}";
        $this->rollbackMigrations[$programId][$key] = $migration;

        $this->logger->debug('Rollback migration added', [
            'program_id' => $programId,
            'from' => $fromVersion,
            'to' => $toVersion,
        ]);
    }

    public function registerCycle18Migrations(string $programId): void
    {
        // Upgrade migration: add new component state schemas
        $this->addMigration($programId, '1.0.0', '1.1.0', function (array $state): array {
            $state['deduplicator'] = $state['deduplicator'] ?? [
                'dedup_keys' => [],
                'ttl' => 60,
                'scope' => 'account',
            ];
            $state['query_engine'] = $state['query_engine'] ?? [
                'registered_queries' => [],
                'loop_patterns' => [],
                'bindings_count' => 0,
            ];
            $state['conversation_state_machine'] = $state['conversation_state_machine'] ?? [
                'per_peer_states' => [],
                'transitions' => [],
            ];
            $state['state_machines'] = $state['state_machines'] ?? [
                'sessions' => [],
            ];
            $state['version'] = '1.1.0';

            return $state;
        });

        // Rollback migration: remove new component state schemas
        $this->addRollbackMigration($programId, '1.1.0', '1.0.0', function (array $state): array {
            unset($state['deduplicator']);
            unset($state['query_engine']);
            unset($state['conversation_state_machine']);
            unset($state['state_machines']);
            $state['version'] = '1.0.0';

            return $state;
        });
    }

    public function registerNewComponentMigrations(string $programId): void
    {
        $this->registerCycle18Migrations($programId);
    }

    public function registerMigrations(string $programId, string $type = 'cycle18'): void
    {
        if ($type === 'cycle18') {
            $this->registerCycle18Migrations($programId);
        }
    }

    public function getMigrations(string $programId): array
    {
        return $this->migrations[$programId] ?? [];
    }

    public function getRollbackMigrations(string $programId): array
    {
        return $this->rollbackMigrations[$programId] ?? [];
    }

    public function upgrade(string $programId, string $targetVersion): UpgradeResult
    {
        $program = $this->getProgram($programId);
        if ($program === null) {
            return new UpgradeResult(false, 'Program not found: '.$programId);
        }

        $currentVersion = $program->getVersion();

        if ($currentVersion === $targetVersion) {
            return new UpgradeResult(true, 'Already at target version', $this->backupState($programId));
        }

        $this->logger->info('Starting upgrade', [
            'program_id' => $programId,
            'from' => $currentVersion,
            'to' => $targetVersion,
        ]);

        // 1. Backup current state
        $backup = $this->backupState($programId);

        // 2. Find migration path
        $migrationPath = $this->findMigrationPath($programId, $currentVersion, $targetVersion);
        if ($migrationPath === null) {
            $this->restoreState($programId, $backup);

            return new UpgradeResult(false, 'No migration path found from '.$currentVersion.' to '.$targetVersion);
        }

        // 3. Run migrations sequentially
        try {
            foreach ($migrationPath as $step) {
                $this->logger->info('Running migration step', [
                    'program_id' => $programId,
                    'from' => $step['from'],
                    'to' => $step['to'],
                ]);

                $migration = $this->migrations[$programId][$step['key']];
                $state = $program->getState();
                $state = $migration($state);
                $program->setState($state);

                // Update program version
                $reflection = new \ReflectionClass($program);
                if ($reflection->hasProperty('version')) {
                    $property = $reflection->getProperty('version');
                    $property->setAccessible(true);
                    $property->setValue($program, $step['to']);
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('Migration failed, rolling back', [
                'program_id' => $programId,
                'error' => $e->getMessage(),
            ]);
            $this->restoreState($programId, $backup);

            return new UpgradeResult(false, 'Migration failed: '.$e->getMessage());
        }

        // 4. Validate new state
        if (! $program->validateState()) {
            $this->logger->error('State validation failed after migration, rolling back', [
                'program_id' => $programId,
            ]);
            $this->restoreState($programId, $backup);

            return new UpgradeResult(false, 'State validation failed after migration');
        }

        // 5. Update cached state
        $this->programStates[$programId] = [
            'version' => $targetVersion,
            'schema_version' => $program->getSchemaVersion(),
            'state' => $program->getState(),
        ];

        $this->logger->info('Upgrade completed successfully', [
            'program_id' => $programId,
            'version' => $targetVersion,
        ]);

        return new UpgradeResult(true, 'Upgrade completed', $backup);
    }

    public function rollback(string $programId, string $targetVersion): RollbackResult
    {
        $program = $this->getProgram($programId);
        if ($program === null) {
            return new RollbackResult(false, 'Program not found: '.$programId);
        }

        $currentVersion = $program->getVersion();

        if ($currentVersion === $targetVersion) {
            return new RollbackResult(true, 'Already at target version');
        }

        $this->logger->info('Starting rollback', [
            'program_id' => $programId,
            'from' => $currentVersion,
            'to' => $targetVersion,
        ]);

        // Find rollback path (reverse migrations)
        $migrationPath = $this->findRollbackPath($programId, $currentVersion, $targetVersion);
        if ($migrationPath === null) {
            return new RollbackResult(false, 'No rollback path found from '.$currentVersion.' to '.$targetVersion);
        }

        // Backup current state before rollback
        $backup = $this->backupState($programId);

        try {
            foreach ($migrationPath as $step) {
                $this->logger->info('Running rollback step', [
                    'program_id' => $programId,
                    'from' => $step['from'],
                    'to' => $step['to'],
                ]);

                // Check if there's a specific rollback migration
                $rollbackKey = "{$step['from']}->{$step['to']}";
                $rollbackMigrations = $this->rollbackMigrations[$programId] ?? [];

                if (! isset($rollbackMigrations[$rollbackKey])) {
                    throw new \RuntimeException("No rollback migration found for rollback from {$step['from']} to {$step['to']}");
                }

                $migration = $rollbackMigrations[$rollbackKey];
                $state = $program->getState();
                $state = $migration($state);
                $program->setState($state);

                // Update program version
                $reflection = new \ReflectionClass($program);
                if ($reflection->hasProperty('version')) {
                    $property = $reflection->getProperty('version');
                    $property->setAccessible(true);
                    $property->setValue($program, $step['to']);
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('Rollback failed', [
                'program_id' => $programId,
                'error' => $e->getMessage(),
            ]);
            $this->restoreState($programId, $backup);

            return new RollbackResult(false, 'Rollback failed: '.$e->getMessage());
        }

        // Validate state after rollback
        if (! $program->validateState()) {
            $this->logger->error('State validation failed after rollback, restoring backup', [
                'program_id' => $programId,
            ]);
            $this->restoreState($programId, $backup);

            return new RollbackResult(false, 'State validation failed after rollback');
        }

        // Update cached state
        $this->programStates[$programId] = [
            'version' => $targetVersion,
            'schema_version' => $program->getSchemaVersion(),
            'state' => $program->getState(),
        ];

        $this->logger->info('Rollback completed successfully', [
            'program_id' => $programId,
            'version' => $targetVersion,
        ]);

        return new RollbackResult(true, 'Rollback completed');
    }

    private function findMigrationPath(string $programId, string $fromVersion, string $toVersion): ?array
    {
        $migrations = $this->migrations[$programId] ?? [];

        // Build adjacency list
        $graph = [];
        foreach ($migrations as $key => $migration) {
            [$from, $to] = explode('->', $key);
            if (! isset($graph[$from])) {
                $graph[$from] = [];
            }
            $graph[$from][$to] = $key;
        }

        // BFS to find path
        $queue = [['version' => $fromVersion, 'path' => []]];
        $visited = [$fromVersion => true];

        while (! empty($queue)) {
            $current = array_shift($queue);

            if ($current['version'] === $toVersion) {
                return $current['path'];
            }

            if (isset($graph[$current['version']])) {
                foreach ($graph[$current['version']] as $nextVersion => $key) {
                    if (! isset($visited[$nextVersion])) {
                        $visited[$nextVersion] = true;
                        $queue[] = [
                            'version' => $nextVersion,
                            'path' => array_merge($current['path'], [
                                ['from' => $current['version'], 'to' => $nextVersion, 'key' => $key],
                            ]),
                        ];
                    }
                }
            }
        }

        return null;
    }

    private function findRollbackPath(string $programId, string $fromVersion, string $toVersion): ?array
    {
        // For rollback, we ONLY use explicit rollback migrations
        $rollbackMigrations = $this->rollbackMigrations[$programId] ?? [];
        $graph = [];

        foreach ($rollbackMigrations as $key => $migration) {
            [$from, $to] = explode('->', $key);
            if (! isset($graph[$from])) {
                $graph[$from] = [];
            }
            $graph[$from][$to] = $key;
        }

        // If no rollback migrations exist, return null (no rollback path)
        if (empty($graph)) {
            return null;
        }

        // BFS to find path
        $queue = [['version' => $fromVersion, 'path' => []]];
        $visited = [$fromVersion => true];

        while (! empty($queue)) {
            $current = array_shift($queue);

            if ($current['version'] === $toVersion) {
                return $current['path'];
            }

            if (isset($graph[$current['version']])) {
                foreach ($graph[$current['version']] as $nextVersion => $key) {
                    if (! isset($visited[$nextVersion])) {
                        $visited[$nextVersion] = true;
                        $queue[] = [
                            'version' => $nextVersion,
                            'path' => array_merge($current['path'], [
                                ['from' => $current['version'], 'to' => $nextVersion, 'key' => $key],
                            ]),
                        ];
                    }
                }
            }
        }

        return null;
    }

    private function backupState(string $programId): array
    {
        $program = $this->programs[$programId];
        $state = [
            'version' => $program->getVersion(),
            'schema_version' => $program->getSchemaVersion(),
            'state' => $program->getState(),
            'timestamp' => time(),
        ];

        $this->logger->debug('State backed up', ['program_id' => $programId]);

        return $state;
    }

    private function restoreState(string $programId, array $backup): void
    {
        $program = $this->programs[$programId] ?? null;
        if ($program !== null && isset($backup['state'])) {
            $program->setState($backup['state']);

            $reflection = new \ReflectionClass($program);
            if ($reflection->hasProperty('version') && isset($backup['version'])) {
                $property = $reflection->getProperty('version');
                $property->setAccessible(true);
                $property->setValue($program, $backup['version']);
            }
        }

        $this->logger->debug('State restored', ['program_id' => $programId]);
    }

    private function getProgramId(VersionedProgram $program): string
    {
        $class = get_class($program);

        return strtolower(str_replace('\\', '_', $class));
    }
}

class UpgradeResult
{
    public function __construct(
        public bool $success,
        public string $message,
        public array $backup = [],
    ) {}
}

class RollbackResult
{
    public function __construct(
        public bool $success,
        public string $message,
    ) {}
}

class_alias('MeRezaRezaei\Teleframe\Telegram\MultiSession\ProgramVersionManager', 'MeRezaRezaei\Telegram\MultiSession\ProgramVersionManager');
class_alias('MeRezaRezaei\Teleframe\Telegram\MultiSession\UpgradeResult', 'MeRezaRezaei\Telegram\MultiSession\UpgradeResult');
class_alias('MeRezaRezaei\Teleframe\Telegram\MultiSession\RollbackResult', 'MeRezaRezaei\Telegram\MultiSession\RollbackResult');
