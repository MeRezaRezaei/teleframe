<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Services;

use Closure;

/**
 * Read-only enforcement seam for the relay vault dispatch — the callable
 * VaultMtprotoAnswerer consumes. Wraps the real vault dispatch and, ONLY when
 * the injected ReadOnlyScope reports read-only, refuses every RPC that is not
 * on an explicit READ-ONLY allow-list with an rpc_error (error_code 400,
 * error_message READONLY_SCOPE_FORBIDDEN); allowed methods pass through to
 * the real dispatch untouched.
 *
 * The allow-list IS the contract — nothing is a write block-list, so a
 * write/state-changing method can never be smuggled past by omission:
 * anything not explicitly listed is refused. Zero regex: exact match /
 * str_starts_with / in_array only (the src/Core + src/Laravel discipline).
 * Namespaces that also carry writes are never prefix-whitelisted whole:
 * contacts read shapes are `contacts.get*` plus the exact
 * search/resolveUsername/resolvePhone names — contacts.addContact/import/
 * block/delete are NOT on the list and are refused. The same holds for the
 * `help.` namespace: only its `help.get*` read shapes are listed by exact
 * name — help.editUserInfo/saveAppLog/setBotUpdatesStatus/acceptTermsOfService/
 * hidePromoData/dismissSuggestion are NOT and are refused. auth.* /
 * account.getAccountTtl / users.getUsers never reach the vault
 * dispatch anyway (VaultMtprotoAnswerer answers them locally), but they stay
 * listed so the contract is complete regardless of future routing.
 *
 * Plain PHP, injectable (constructor), never touches a session string. The
 * scope is consulted per call, so the middleware's request flag flips the
 * seam live between requests in a shared process, and an unmarked scope makes
 * the wrapper behave exactly like the raw dispatch.
 *
 * PER-CONNECTION ARMING (finding C5): a request frame is the wrong source for
 * a socket, and the only producer of one (ScopeTelegramReadOnly) never runs in
 * the `teleframe:relay` console daemon — so a scope-only check was permanently
 * false in the one process that enforces anything. The dispatch therefore also
 * accepts the per-CONNECTION ticket grant the answerer threads in as its third
 * argument and consults a RelayReadOnlyGrants registry for that identity. With
 * a registry wired the guard is armed from the ticket the socket redeemed, and
 * an UNDETERMINABLE grant (no entry, expired entry, malformed identity) is
 * read-only: deny by default, so a guard that cannot decide never decides
 * "allow". With NO registry the historical scope-only behaviour is preserved
 * exactly (standalone / unwired), and `$enforce === false` is the documented
 * opt-out.
 */
final class ReadOnlyVaultDispatch
{
    public const ERROR_CODE = 400;

    public const ERROR_MESSAGE = 'READONLY_SCOPE_FORBIDDEN';

    /** Prefix contract: every method under these namespaces is a read shape. */
    private const READ_ONLY_PREFIXES = [
        'updates.',
        'contacts.get',
        'channels.get',
        'photos.get',
    ];

    /**
     * auth.* is NEVER prefix-whitelisted: the namespace carries
     * auth.exportLoginToken / auth.exportAuthorization, which mint
     * TRANSFERABLE account tokens — a latent account-takeover primitive, and
     * therefore writes. They are called out by exact name below so the
     * classification is visible instead of hiding behind a prefix, and so a
     * future read shape under auth.* has to be listed deliberately.
     */
    private const AUTH_WRITE_METHODS = [
        'auth.exportLoginToken',
        'auth.exportAuthorization',
    ];

    /**
     * Exact read-only methods outside the prefix contract (READ variants
     * only — a method showing up here under a namespace that also carries
     * writes is whitelisted by name, never by namespace).
     */
    private const READ_ONLY_METHODS = [
        'users.getUsers',
        'users.getFullUser',
        'messages.getDialogs',
        'messages.getHistory',
        'messages.getMessages',
        'messages.getMessageReactionsList',
        'messages.getSearch',
        'messages.getMessageEditData',
        'messages.getPeerDialogs',
        'messages.search', // catalog read primitive (LOW R2-WH-3); messages.getSearch does not exist in the schema
        'messages.getChats',
        'messages.getFullChat',
        'messages.checkChatInvite',
        'contacts.search',
        'contacts.resolveUsername',
        'contacts.resolvePhone',
        'account.getAccountTtl',
        'account.getPassword',
        'account.getPrivacy',
        'account.getAuthorizations',
        'account.getNotifySettings',
        'account.getContactSignUpNotification',
        'account.checkUsername',
        // help.* read shapes only (HIGH R2-WH-1): the catalog's non-get
        // help.* methods — help.editUserInfo, saveAppLog, setBotUpdatesStatus,
        // acceptTermsOfService, hidePromoData, dismissSuggestion — mutate state
        // and MUST NOT be listed. Every name below verified against
        // src/Schema/schema/methods-mtproto.json.
        'help.getAppConfig',
        'help.getAppUpdate',
        'help.getCdnConfig',
        'help.getConfig',
        'help.getCountriesList',
        'help.getDeepLinkInfo',
        'help.getInviteText',
        'help.getNearestDc',
        'help.getPassportConfig',
        'help.getPeerColors',
        'help.getPeerProfileColors',
        'help.getPremiumPromo',
        'help.getPromoData',
        'help.getRecentMeUrls',
        'help.getSupport',
        'help.getSupportName',
        'help.getTermsOfServiceUpdate',
        'help.getTimezonesList',
        'help.getUserInfo',
    ];

    /** @var Closure(string, array<string, mixed>): array<string, mixed> */
    private readonly Closure $dispatch;

    /**
     * @param  callable(string $method, array<string, mixed> $params): array<string, mixed>  $dispatch  the real vault dispatch
     * @param  RelayReadOnlyGrants|null  $grants  per-connection grant registry consulted with the
     *                                            dispatch's own ticket grant; null keeps the
     *                                            historical scope-only decision (no per-connection
     *                                            arming, standalone behaviour unchanged)
     * @param  bool  $enforce  when false the registry is not consulted at all — the documented
     *                         opt-out (teleframe.relay.read_only.enforce), unsafe by design
     */
    public function __construct(
        callable $dispatch,
        private readonly ReadOnlyScope $scope,
        private readonly ?RelayReadOnlyGrants $grants = null,
        private readonly bool $enforce = true,
    ) {
        $this->dispatch = Closure::fromCallable($dispatch);
    }

    /**
     * Dispatch one RPC: in a read-only scope a non-allow-listed method is
     * refused without ever reaching the underlying dispatch; everything else
     * passes through.
     *
     * The optional third `$grant` is the PER-CONNECTION ticket grant the
     * VaultMtprotoAnswerer hands to the dispatch (normalized to
     * {account_id, user_id}) — the source the daemon arms the guard from.
     * Two connections never share it, so neither can inherit the other's
     * intent.
     *
     * @param  array<string, mixed>  $params
     * @param  array{account_id?: mixed, user_id?: mixed}|null  $grant
     * @return array<string, mixed>
     */
    public function __invoke(string $method, array $params, ?array $grant = null): array
    {
        if ($this->mustRefuse($grant) && ! $this->isReadOnlyMethod($method)) {
            return [
                '_' => 'rpc_error',
                'error_code' => self::ERROR_CODE,
                'error_message' => self::ERROR_MESSAGE,
            ];
        }

        return ($this->dispatch)($method, $params);
    }

    /**
     * The read-only decision for ONE RPC on ONE connection: the host scope
     * (HTTP request frame / operator pin) OR the per-connection ticket grant.
     * Deny by default — the registry answers read-only for any grant it
     * cannot determine.
     *
     * @param  array{account_id?: mixed, user_id?: mixed}|null  $grant
     */
    private function mustRefuse(?array $grant): bool
    {
        if ($this->scope->isReadOnly()) {
            return true;
        }

        if ($this->grants === null || ! $this->enforce) {
            return false;
        }

        return $this->grants->isReadOnly($grant);
    }

    private function isReadOnlyMethod(string $method): bool
    {
        if (in_array($method, self::AUTH_WRITE_METHODS, true)) {
            return false;
        }

        foreach (self::READ_ONLY_PREFIXES as $prefix) {
            if (str_starts_with($method, $prefix)) {
                return true;
            }
        }

        return in_array($method, self::READ_ONLY_METHODS, true);
    }
}
