<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Services;

use MeRezaRezaei\Teleframe\Control\RelayContext;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;
use RuntimeException;
use UnexpectedValueException;

/**
 * Production scope resolver for the control layer: turns a RelayContext
 * (one vault account) into the live user-client RelayScope behind the
 * AccountRelay seam — WITHOUT dialing.
 *
 * Dependency-lite by design: the vault loader and client factory are
 * constructor-injected callables, so tests need no Laravel container,
 * cache, or DB. Nulls fall back to lazy production defaults (resolved
 * only when forAccount() actually runs, never at construction).
 *
 * The session string is the only credential in play and stays internal to
 * this class: it is read from the vault, handed straight to the client
 * factory, and never logged, echoed, or returned through any public API.
 * RelayScope carries only the built client + dc id.
 */
final class RelaySessionResolver
{
    /** @var (callable(string, int): ?string)|null */
    private $vaultLoader;

    /** @var (callable(string, int): object)|null */
    private $clientFactory;

    /**
     * @param  callable(string $accountIdRef, int $dcId): ?string|null  $vaultLoader
     * @param  callable(string $session, int $dcId): object|null  $clientFactory
     */
    public function __construct(
        ?callable $vaultLoader = null,
        ?callable $clientFactory = null,
    ) {
        $this->vaultLoader = $vaultLoader;
        $this->clientFactory = $clientFactory;
    }

    public function forAccount(RelayContext $ctx): RelayScope
    {
        /** @var mixed $session */
        $session = ($this->vaultLoader ?? $this->defaultVaultLoader())(
            (string) $ctx->accountId,
            $ctx->dcId,
        );

        if (! is_string($session) || $session === '') {
            throw new RuntimeException(sprintf(
                'relay: no vault session for account id=%d (dc=%d)',
                $ctx->accountId,
                $ctx->dcId,
            ));
        }

        /** @var mixed $client */
        $client = ($this->clientFactory ?? $this->defaultClientFactory())($session, $ctx->dcId);

        if (! is_object($client)) {
            throw new UnexpectedValueException('relay: client factory must return an object, got '.get_debug_type($client));
        }

        return new RelayScope($client, $ctx->dcId);
    }

    /**
     * Production default vault loader — the credential vault's account
     * row. Call chain: TelegramAccount::find(accountId) over
     * `telegram_accounts` (id, label, dc_id, encrypted `session`
     * columns), restricted to `type = user` rows; the Eloquent
     * `encrypted` cast decrypts the stored blob inside the accessor, so
     * the returned value is the live plaintext SessionData export string.
     *
     * The teleclient.backup.vault-factory bind is the FILE-backup chunk
     * vault (TelegramVault/VaultInterface over a backup channel) and is
     * intentionally NOT consulted here: account session strings never
     * enter that channel — they live column-wise in telegram_accounts.
     */
    private function defaultVaultLoader(): callable
    {
        return static function (string $accountIdRef, int $dcId): ?string {
            $account = TelegramAccount::query()->find((int) $accountIdRef);

            if (! $account instanceof TelegramAccount || $account->type !== TelegramAccount::TYPE_USER) {
                return null;
            }

            $session = $account->getAttribute('session');

            return is_string($session) && $session !== '' ? $session : null;
        };
    }

    /**
     * Production default client factory: TeleframeClient::user(session,
     * dcId) through the container singleton (config api_id/api_hash feed
     * the defaults). Construction is lazy — user() wraps the session in a
     * UserAccountScope/MTProtoClient without opening a socket; the
     * encrypted connection is established on the first RPC call
     * (MTProtoClient::ensureConnection()), so forAccount() itself never
     * dials.
     */
    private function defaultClientFactory(): callable
    {
        return static function (string $session, int $dcId): object {
            if (! function_exists('app')) {
                throw new RuntimeException(
                    'relay: default client factory needs the Laravel container (app()); inject a clientFactory callable',
                );
            }

            /** @var TeleframeClient $client */
            $client = app(TeleframeClient::class);

            return $client->user(session: $session, dcId: $dcId);
        };
    }
}
