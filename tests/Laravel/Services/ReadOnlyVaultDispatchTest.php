<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Services;

use Closure;
use Illuminate\Http\Request;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Laravel\Http\Middleware\ScopeTelegramReadOnly;
use MeRezaRezaei\Teleframe\Laravel\Services\ReadOnlyScope;
use MeRezaRezaei\Teleframe\Laravel\Services\ReadOnlyVaultDispatch;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayActiveAccount;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayFactory;
use MeRezaRezaei\Teleframe\Laravel\Services\RelaySessionResolver;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayVaultTerminalFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cycle 6 — Laravel-session scoped READ-ONLY client. The embedded tgweb
 * client keeps its default standalone behaviour; when a Laravel-user session
 * is present, the relay vault dispatch is wrapped in ReadOnlyVaultDispatch so
 * every state-changing RPC is refused with an rpc_error (400,
 * READONLY_SCOPE_FORBIDDEN). Plain PHP — the underlying dispatch is a
 * recorder closure, the auth check in the middleware is an injected callable,
 * so nothing touches a guard, a session string or the wire.
 */
final class ReadOnlyVaultDispatchTest extends TestCase
{
    public function test_write_method_is_refused_with_rpc_error_in_read_only_scope(): void
    {
        $calls = [];
        $scope = new ReadOnlyScope;
        $dispatch = new ReadOnlyVaultDispatch($this->recorder($calls), $scope);
        $scope->begin();

        $result = $dispatch('messages.sendMessage', ['peer_id' => 1, 'message' => 'hi']);

        self::assertSame([
            '_' => 'rpc_error',
            'error_code' => 400,
            'error_message' => 'READONLY_SCOPE_FORBIDDEN',
        ], $result);
        self::assertSame([], $calls, 'a refused write never reaches the underlying dispatch');
    }

    public function test_read_method_passes_through_to_the_underlying_dispatch(): void
    {
        $calls = [];
        $scope = new ReadOnlyScope;
        $dispatch = new ReadOnlyVaultDispatch($this->recorder($calls), $scope);
        $scope->begin();

        $result = $dispatch('messages.getDialogs', ['limit' => 10]);

        self::assertSame([['messages.getDialogs', ['limit' => 10]]], $calls);
        self::assertSame(['_' => 'messages.getDialogs', 'ok' => true], $result);
    }

    public function test_read_only_prefix_contract_is_allowed(): void
    {
        $calls = [];
        $scope = new ReadOnlyScope;
        $dispatch = new ReadOnlyVaultDispatch($this->recorder($calls), $scope);
        $scope->begin();

        foreach (['updates.getState', 'channels.getChannels', 'photos.getUserPhotos', 'contacts.getContacts'] as $method) {
            $result = $dispatch($method, []);
            self::assertSame(['_' => $method, 'ok' => true], $result, $method.' rides the allow-list prefix contract');
        }
    }

    /**
     * C5 follow-up: the `auth.export` PREFIX admitted auth.exportLoginToken
     * and auth.exportAuthorization — both mint TRANSFERABLE account tokens, a
     * latent account-takeover primitive. They are not reachable through the
     * shipped answerer (it answers them locally), but any other consumer of
     * this guard would have inherited them. The prefix is gone; the two names
     * are classified as writes explicitly.
     */
    public function test_auth_export_token_minting_is_classified_as_a_write(): void
    {
        $calls = [];
        $scope = new ReadOnlyScope;
        $dispatch = new ReadOnlyVaultDispatch($this->recorder($calls), $scope);
        $scope->begin();

        foreach (['auth.exportLoginToken', 'auth.exportAuthorization'] as $method) {
            $result = $dispatch($method, []);
            self::assertSame('rpc_error', $result['_'], $method.' mints a transferable token and must be refused');
            self::assertSame('READONLY_SCOPE_FORBIDDEN', $result['error_message']);
        }
        self::assertSame([], $calls, 'no transferable-token mint reaches the underlying dispatch');
    }

    public function test_help_namespace_read_shapes_pass_and_writes_are_refused(): void
    {
        $calls = [];
        $scope = new ReadOnlyScope;
        $dispatch = new ReadOnlyVaultDispatch($this->recorder($calls), $scope);
        $scope->begin();

        $result = $dispatch('help.getConfig', []);
        self::assertSame(['_' => 'help.getConfig', 'ok' => true], $result, 'help.getConfig is a catalog read shape and MUST pass');

        foreach (['help.editUserInfo', 'help.saveAppLog', 'help.setBotUpdatesStatus', 'help.acceptTermsOfService', 'help.hidePromoData', 'help.dismissSuggestion'] as $method) {
            $result = $dispatch($method, []);
            self::assertSame('rpc_error', $result['_'], $method.' is a help WRITE and must be refused');
            self::assertSame('READONLY_SCOPE_FORBIDDEN', $result['error_message']);
        }
        self::assertSame([['help.getConfig', []]], $calls, 'no refused help write reaches the underlying dispatch');
    }

    public function test_messages_search_read_shape_passes_through(): void
    {
        $calls = [];
        $scope = new ReadOnlyScope;
        $dispatch = new ReadOnlyVaultDispatch($this->recorder($calls), $scope);
        $scope->begin();

        $result = $dispatch('messages.search', ['peer' => 1, 'q' => 'hello']);

        self::assertSame([['messages.search', ['peer' => 1, 'q' => 'hello']]], $calls, 'messages.search is the catalog read primitive');
        self::assertSame(['_' => 'messages.search', 'ok' => true], $result);
    }

    public function test_contacts_namespace_never_whitelists_its_writes(): void
    {
        $calls = [];
        $scope = new ReadOnlyScope;
        $dispatch = new ReadOnlyVaultDispatch($this->recorder($calls), $scope);
        $scope->begin();

        foreach (['contacts.addContact', 'contacts.importContacts', 'contacts.block', 'contacts.deleteContacts'] as $method) {
            $result = $dispatch($method, []);
            self::assertSame('rpc_error', $result['_'], $method.' is a contacts WRITE and must be refused');
            self::assertSame('READONLY_SCOPE_FORBIDDEN', $result['error_message']);
        }
        self::assertSame([], $calls, 'no refused write reaches the underlying dispatch');
    }

    public function test_default_scope_unchanged_write_dispatches_through(): void
    {
        $calls = [];
        $scope = new ReadOnlyScope;
        $dispatch = new ReadOnlyVaultDispatch($this->recorder($calls), $scope);

        $result = $dispatch('messages.sendMessage', ['peer_id' => 1, 'message' => 'hi']);

        self::assertSame([['messages.sendMessage', ['peer_id' => 1, 'message' => 'hi']]], $calls, 'an unmarked scope keeps standalone full behaviour');
        self::assertSame(['_' => 'messages.sendMessage', 'ok' => true], $result);
    }

    public function test_scope_frame_is_closed_by_end(): void
    {
        $scope = new ReadOnlyScope;
        $scope->begin();
        self::assertTrue($scope->isReadOnly());

        $scope->end();

        self::assertFalse($scope->isReadOnly());
    }

    public function test_operator_pinned_resolver_forces_read_only_outside_request_frames(): void
    {
        $scope = new ReadOnlyScope(static fn (): bool => true);

        self::assertTrue($scope->isReadOnly(), 'a pinned resolver wins before the request-frame counter');
    }

    public function test_middleware_marks_the_request_read_only_when_a_user_session_is_present(): void
    {
        $scope = new ReadOnlyScope;
        $middleware = new ScopeTelegramReadOnly($scope, static fn (): bool => true);
        // Laravel hands terminate() the SAME Request instance handle() was
        // given — the frame ownership flag rides on it.
        $request = Request::create('http://localhost/tg');

        $response = $middleware->handle($request, static fn (Request $request): Response => new Response('ok'));

        self::assertTrue($scope->isReadOnly(), 'a Laravel-user session opens the read-only frame for the request');

        $middleware->terminate($request, $response);

        self::assertFalse($scope->isReadOnly(), 'the frame closes when the request terminates');
    }

    /**
     * MEDIUM (adjacent to C5): begin() was conditional on a session but the
     * terminating end() was UNCONDITIONAL. Under a long-lived worker
     * (Octane/Swoole/RoadRunner) a session-less request decremented the shared
     * ReadOnlyScope counter, prematurely closing ANOTHER in-flight request's
     * read-only frame — i.e. widening that request's authority mid-flight.
     */
    public function test_a_session_less_request_never_closes_another_requests_frame(): void
    {
        $scope = new ReadOnlyScope;

        // Request A: a session-backed request opens the frame.
        $requestA = Request::create('http://localhost/tg');
        $middlewareA = new ScopeTelegramReadOnly($scope, static fn (): bool => true);
        $responseA = $middlewareA->handle($requestA, static fn (Request $request): Response => new Response('a'));

        // Request B: a session-LESS request, interleaved on the same worker.
        $requestB = Request::create('http://localhost/tf');
        $middlewareB = new ScopeTelegramReadOnly($scope, static fn (): bool => false);
        $responseB = $middlewareB->handle($requestB, static fn (Request $request): Response => new Response('b'));
        $middlewareB->terminate($requestB, $responseB);

        self::assertTrue($scope->isReadOnly(), 'request B closing must not close request A read-only frame');
        self::assertTrue(
            $scope->isReadOnly(),
            'a worker keeps the frame armed until its OWN owner terminates',
        );

        $middlewareA->terminate($requestA, $responseA);

        self::assertFalse($scope->isReadOnly(), 'and the owner still closes exactly its own frame');
    }

    public function test_terminate_without_a_handle_opened_nothing_leaves_the_scope_alone(): void
    {
        $scope = new ReadOnlyScope;
        $scope->begin();
        $middleware = new ScopeTelegramReadOnly($scope, static fn (): bool => false);

        $middleware->terminate(Request::create('http://localhost/tf'), new Response('b'));

        self::assertTrue($scope->isReadOnly(), 'a terminate() for a request that opened no frame is a no-op');
    }

    public function test_middleware_leaves_the_scope_full_without_a_user_session(): void
    {
        $scope = new ReadOnlyScope;
        $middleware = new ScopeTelegramReadOnly($scope, static fn (): bool => false);

        $middleware->handle(Request::create('http://localhost/tg'), static fn (Request $request): Response => new Response('ok'));

        self::assertFalse($scope->isReadOnly(), 'no user session → standalone full behaviour, no frame opened');
    }

    public function test_relay_vault_terminal_factory_wraps_dispatch_only_when_a_scope_is_injected(): void
    {
        $factory = new RelayFactory(new RelaySessionResolver(
            vaultLoader: static fn (string $ref, int $dc): ?string => null,
        ));
        $active = new RelayActiveAccount(new InMemoryCache, $factory);

        $raw = (new RelayVaultTerminalFactory($factory, $active))->dispatch();
        self::assertInstanceOf(Closure::class, $raw, 'no scope injected → raw dispatch, standalone unchanged');

        $wrapped = (new RelayVaultTerminalFactory($factory, $active, readOnlyScope: new ReadOnlyScope))->dispatch();
        self::assertInstanceOf(ReadOnlyVaultDispatch::class, $wrapped, 'scope injected → dispatch is the read-only enforcement seam');
    }

    /**
     * @param  list<array{0: string, 1: array<string, mixed>}>  $calls
     * @return callable(string, array<string, mixed>): array<string, mixed>
     */
    private function recorder(array &$calls): callable
    {
        return static function (string $method, array $params) use (&$calls): array {
            $calls[] = [$method, $params];

            return ['_' => $method, 'ok' => true];
        };
    }
}
