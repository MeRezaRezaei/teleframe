<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use MeRezaRezaei\Teleframe\Core\Support\EnvFile;
use MeRezaRezaei\Teleframe\Backup\TelegramVault;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Reviewer I1: live-gated smoke for the telegram vault driver. Skipped
 * unless TELECLIENT_LIVE is set AND a user session resolves — env
 * loading mirrors teleframe's test-e2e (EnvFile::read on the sibling
 * ../teleframe/.env, plain getenv as fallback). Manual gate:
 *
 *     TELECLIENT_LIVE=1 vendor/bin/phpunit --filter LiveVaultSmoke
 *
 * Exercises the real scopeApi map — channel find-or-create, the
 * upload.saveFilePart loop, messages.sendMedia, messages.search, the
 * upload.getFile download loop — under a dated smoke set id so every
 * day gets its own channel (teleframe-smoke-YYYY-MM-DD).
 */
final class LiveVaultSmokeTest extends TestCase
{
    private const RETRIES = 3;     // messages.search indexation lag
    private const RETRY_DELAY = 3; // seconds between attempts

    /**
     * Live credentials, test-e2e pattern: sibling .env first, real
     * environment as fallback.
     *
     * @return array{session: string, apiId: int, apiHash: string}
     */
    private static function liveEnv(): array
    {
        $env = EnvFile::read(dirname(__DIR__, 3) . '/teleframe/.env');

        return [
            'session' => (string) ($env['TELEGRAM_USER_SESSION'] ?? getenv('TELEGRAM_USER_SESSION') ?: ''),
            'apiId' => (int) ($env['TELEGRAM_API_ID'] ?? getenv('TG_API_ID') ?: 0),
            'apiHash' => (string) ($env['TELEGRAM_API_HASH'] ?? getenv('TG_API_HASH') ?: ''),
        ];
    }

    public function test_live_vault_chunk_roundtrip_and_manifest_latest(): void
    {
        $flag = getenv('TELECLIENT_LIVE');
        $env = self::liveEnv();
        if ($flag === false || $flag === '' || $flag === '0' || $env['session'] === '') {
            $this->markTestSkipped(
                'live smoke: set TELECLIENT_LIVE=1 and provide TELEGRAM_USER_SESSION '
                . '(../teleframe/.env or environment) to run',
            );
        }

        $scope = (new TeleframeClient($env['apiId'], $env['apiHash']))->fromSession($env['session']);
        $scope->mtproto->live(); // bare PHPUnit: no app/config — set live mode explicitly
        $vault = TelegramVault::forScope($scope, 'teleframe-smoke-' . date('Y-m-d'));

        $bytes = random_bytes(1024);
        $hash = hash('sha256', $bytes);
        $vault->putChunk($hash, $bytes);
        self::assertSame($bytes, self::retry(fn (): string => $vault->getChunk($hash)));

        $manifest = ['version' => 1, 'kind' => 'live-smoke', 'chunks' => [$hash]];
        $vault->putManifest((string) json_encode($manifest, JSON_THROW_ON_ERROR));
        $latest = self::retry(function () use ($vault, $hash): array {
            $current = $vault->getLatestManifest() ?? [];
            if (!in_array($hash, $current['chunks'] ?? [], true)) {
                // search index trails writes: a latest manifest that does not
                // reference the chunk we just posted is stale, not final
                throw new RuntimeException('search index stale: latest manifest does not reference the fresh chunk yet');
            }

            return $current;
        });
        self::assertSame($manifest, $latest);
    }

    /**
     * Telegram's message search index trails writes by a moment; retry a
     * fresh fetch a few times before letting the failure land.
     *
     * @template T
     *
     * @param callable(): T $fetch
     *
     * @return T
     */
    private static function retry(callable $fetch): mixed
    {
        $last = new RuntimeException('retry: fetch never ran');
        for ($attempt = 1; $attempt <= self::RETRIES; $attempt++) {
            try {
                return $fetch();
            } catch (RuntimeException $e) {
                $last = $e;
                if ($attempt < self::RETRIES) {
                    sleep(self::RETRY_DELAY);
                }
            }
        }

        throw $last;
    }
}
