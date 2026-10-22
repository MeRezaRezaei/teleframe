<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Telegram\MultiSession;

use MeRezaRezaei\Teleframe\Bot\Services\BotClient;
use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Singleton poller: exactly ONE getUpdates long-poller per bot token.
 */
class SingletonPoller
{
    /** @var array<string, self> */
    private static array $instances = [];

    public string $botToken;
    public LoggerInterface $logger;
    /** @var array<string, mixed> */
    public array $config;
    public int $maxRetries;
    public int $retryDelayMs;
    public int $timeoutSeconds;

    private ?string $pidFile = null;
    private bool $ownsPidFile = false;
    private bool $running = false;
    private mixed $onUpdate = null;
    private mixed $botClient = null;
    private int $offset = 0;

    public function __construct(
        mixed $botToken = '',
        mixed $logger = null,
        mixed $config = [],
        ?string $pidFile = null,
        mixed $botClient = null,
    ) {
        if (is_array($botToken)) {
            $cfg = $botToken;
            $botToken = $cfg['bot_token'] ?? $cfg['botToken'] ?? $cfg['token'] ?? '';
            if ($logger === null && isset($cfg['logger'])) {
                $logger = $cfg['logger'];
            }
            if (($config === [] || $config === null) && isset($cfg['config'])) {
                $config = $cfg['config'];
            }
            if ($pidFile === null && isset($cfg['pid_file'])) {
                $pidFile = $cfg['pid_file'];
            }
            if ($pidFile === null && isset($cfg['pidFile'])) {
                $pidFile = $cfg['pidFile'];
            }
            if ($botClient === null && isset($cfg['bot_client'])) {
                $botClient = $cfg['bot_client'];
            }
        }

        $resolvedLogger = null;
        $resolvedConfig = [];
        foreach ([$logger, $config] as $candidate) {
            if ($candidate instanceof LoggerInterface) {
                $resolvedLogger = $candidate;
            } elseif (is_array($candidate)) {
                $resolvedConfig = $candidate;
            }
        }
        $logger = $resolvedLogger;
        $config = $resolvedConfig;

        if ($logger instanceof LoggerInterface) {
            $this->logger = $logger;
        } else {
            $this->logger = new NullLogger();
        }

        $this->botToken = (string) $botToken;
        if ($pidFile === null) {
            $pidFile = $config['pid_file'] ?? $config['pidFile'] ?? null;
        }
        $normalized = $this->normalizeConfig($config);
        $this->config = $normalized;
        $this->maxRetries = $normalized['max_retries'];
        $this->retryDelayMs = $normalized['retry_delay_ms'];
        $this->timeoutSeconds = $normalized['timeout_seconds'];
        $this->pidFile = $pidFile;
        $this->botClient = $botClient;
    }

    /** @param array<string, mixed> $config */
    private function normalizeConfig(array $config): array
    {
        $maxRetries = $config['max_retries'] ?? $config['maxRetries'] ?? 3;
        $retryDelay = $config['retry_delay_ms'] ?? $config['retryDelayMs'] ?? $config['retry_delay'] ?? 1000;
        $timeout = $config['timeout_seconds'] ?? $config['timeoutSeconds'] ?? $config['timeout'] ?? 30;

        return [
            'max_retries' => (int) $maxRetries,
            'retry_delay_ms' => (int) $retryDelay,
            'timeout_seconds' => (int) $timeout,
        ];
    }

    public static function getPoller(
        mixed $botToken = '',
        mixed $logger = null,
        mixed $config = [],
        ?string $pidFile = null,
        mixed $botClient = null,
    ): self {
        if (is_array($botToken)) {
            $cfg = $botToken;
            $botToken = $cfg['bot_token'] ?? $cfg['botToken'] ?? $cfg['token'] ?? '';
            if ($logger === null && isset($cfg['logger'])) {
                $logger = $cfg['logger'];
            }
            if (($config === [] || $config === null) && isset($cfg['config'])) {
                $config = $cfg['config'];
            }
            if ($pidFile === null && isset($cfg['pid_file'])) {
                $pidFile = $cfg['pid_file'];
            }
            if ($pidFile === null && isset($cfg['pidFile'])) {
                $pidFile = $cfg['pidFile'];
            }
            if ($botClient === null && isset($cfg['bot_client'])) {
                $botClient = $cfg['bot_client'];
            }
        }

        $resolvedLogger = null;
        $resolvedConfig = [];
        foreach ([$logger, $config] as $candidate) {
            if ($candidate instanceof LoggerInterface) {
                $resolvedLogger = $candidate;
            } elseif (is_array($candidate)) {
                $resolvedConfig = $candidate;
            }
        }
        $logger = $resolvedLogger;
        $config = $resolvedConfig;

        $key = (string) $botToken;
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self(
                $botToken,
                $logger,
                $config,
                $pidFile,
                $botClient,
            );
        }

        return self::$instances[$key];
    }

    public static function shutdown(mixed $botToken): void
    {
        $key = is_array($botToken)
            ? (string) ($botToken['bot_token'] ?? $botToken['botToken'] ?? $botToken['token'] ?? '')
            : (string) $botToken;

        if (isset(self::$instances[$key])) {
            self::$instances[$key]->stop();
            unset(self::$instances[$key]);
        }
    }

    public static function reset(): void
    {
        foreach (self::$instances as $poller) {
            $poller->stop();
        }
        self::$instances = [];
    }

    public static function instances(): array
    {
        return self::$instances;
    }

    public function stop(): void
    {
        $this->running = false;
        $this->releasePidLock();
    }

    public function isRunning(): bool
    {
        return $this->running;
    }

    public function onUpdate(callable $callback): self
    {
        $this->onUpdate = $callback;

        return $this;
    }

    public function getOnUpdate(): mixed
    {
        return $this->onUpdate;
    }

    public function setBotClient(?BotClient $client): self
    {
        $this->botClient = $client;

        return $this;
    }

    public function getBotClient(): mixed
    {
        return $this->botClient;
    }

    private function botClient(): mixed
    {
        if ($this->botClient === null) {
            $this->botClient = new BotClient(
                $this->botToken,
                null,
                $this->timeoutSeconds,
                $this->maxRetries,
            );
        }

        return $this->botClient;
    }

    public function isConflict(Throwable $e): bool
    {
        if ($e instanceof TelegramException && $e->getCode() === 409) {
            return true;
        }

        $msg = strtolower($e->getMessage());

        return str_contains($msg, '409')
            || str_contains($msg, 'conflict')
            || str_contains($msg, 'terminated by other getupdates');
    }

    public function handleConflict(Throwable $e): void
    {
        $this->logger->warning('SingletonPoller 409 Conflict detected; exiting cleanly.');
        $this->running = false;
        $this->releasePidLock();
    }

    public function handle409(Throwable $e): void
    {
        $this->handleConflict($e);
    }

    public function pollOnce(?callable $onUpdate = null): array
    {
        $callback = $onUpdate ?? $this->onUpdate;
        $client = $this->botClient();

        try {
            $response = $client->call('getUpdates', [
                'offset' => $this->offset,
                'limit' => 100,
                'timeout' => $this->timeoutSeconds,
            ]);
        } catch (Throwable $e) {
            if ($this->isConflict($e)) {
                $this->logger->warning('SingletonPoller 409 Conflict detected for bot token; exiting cleanly.');
                $this->running = false;
                $this->releasePidLock();

                return [];
            }

            $this->logger->error('SingletonPoller getUpdates failed: ' . $e->getMessage());

            throw $e;
        }

        $updates = [];
        if (is_array($response) && isset($response['result']) && is_array($response['result'])) {
            $updates = $response['result'];
        }

        foreach ($updates as $update) {
            if (!is_array($update)) {
                continue;
            }

            $rawId = $update['update_id'] ?? null;
            if (is_int($rawId) || (is_string($rawId) && ctype_digit($rawId))) {
                $this->offset = max($this->offset, (int) $rawId + 1);
            }

            if ($callback !== null) {
                $callback($update);
            }
        }

        return $updates;
    }

    public function poll(?callable $onUpdate = null): void
    {
        $this->running = true;
        $callback = $onUpdate ?? $this->onUpdate;

        if ($this->pidFile !== null) {
            if (!$this->acquirePidLock($this->pidFile)) {
                $this->logger->warning('SingletonPoller could not acquire PID lock; another instance may be running.');
                $this->running = false;

                return;
            }
        }

        $retries = 0;
        while ($this->running) {
            try {
                $this->pollOnce($callback);
                $retries = 0;
            } catch (Throwable $e) {
                if ($this->isConflict($e)) {
                    $this->logger->warning('SingletonPoller 409 Conflict detected; exiting cleanly.');
                    $this->running = false;
                    break;
                }

                $retries++;
                $this->logger->error('SingletonPoller error: ' . $e->getMessage());

                if ($this->maxRetries >= 0 && $retries >= $this->maxRetries) {
                    $this->logger->error('SingletonPoller max retries reached; stopping.');
                    $this->running = false;
                    break;
                }

                $this->sleepMs($this->retryDelayMs);
            }
        }

        $this->releasePidLock();
    }

    public function run(?callable $onUpdate = null): void
    {
        $this->poll($onUpdate);
    }

    public function start(?callable $onUpdate = null): void
    {
        $this->poll($onUpdate);
    }

    public function startPolling(?callable $onUpdate = null): void
    {
        $this->poll($onUpdate);
    }

    private function sleepMs(int $ms): void
    {
        if ($ms <= 0) {
            return;
        }

        usleep($ms * 1000);
    }

    public function acquirePidLock(?string $path = null): bool
    {
        $target = $path ?? $this->pidFile;
        if ($target === null) {
            return true;
        }

        $dir = dirname($target);
        if ($dir !== '' && $dir !== '.' && !is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        if (file_exists($target)) {
            $existing = (int) trim((string) @file_get_contents($target));
            if ($existing > 0 && $this->processAlive($existing)) {
                $this->logger->warning('SingletonPoller PID lock held by running process ' . $existing . '.');

                return false;
            }
        }

        $written = @file_put_contents($target, (string) getmypid(), LOCK_EX);
        if ($written === false) {
            return false;
        }

        $this->pidFile = $target;
        $this->ownsPidFile = true;

        return true;
    }

    public function releasePidLock(): void
    {
        if ($this->ownsPidFile && $this->pidFile !== null && file_exists($this->pidFile)) {
            $content = (string) @file_get_contents($this->pidFile);
            if (trim($content) === (string) getmypid()) {
                @unlink($this->pidFile);
            }
        }

        $this->ownsPidFile = false;
    }

    public function lockPidFile(?string $path = null): bool
    {
        return $this->acquirePidLock($path);
    }

    public function unlockPidFile(): void
    {
        $this->releasePidLock();
    }

    public function pidFilePath(): ?string
    {
        return $this->pidFile;
    }

    private function processAlive(int $pid): bool
    {
        if (function_exists('posix_kill')) {
            return @posix_kill($pid, 0);
        }

        return $pid === getmypid();
    }

    /** @return array<string, mixed> */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    public function getRetryDelayMs(): int
    {
        return $this->retryDelayMs;
    }

    public function getTimeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }
}
