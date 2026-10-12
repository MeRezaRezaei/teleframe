<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Notify\DialogPeerResolver;
use MeRezaRezaei\Teleframe\Notify\LazyUserScope;
use MeRezaRezaei\Teleframe\Notify\NotifyException;
use PHPUnit\Framework\TestCase;

/**
 * `DialogPeerResolver` — the shipped `peerResolver` seam, against a
 * recording scope double.
 *
 * The reachability proof (UserScopeTransportReachabilityTest) shows a CONFIG
 * VALUE reaching a real send through this class; this file pins the edges
 * that a send must refuse rather than guess, because a resolver that invents
 * an `access_hash` produces a send that either fails at Telegram or, worse,
 * is believed to have reached a human when it did not.
 */
final class PeerResolverSeamTest extends TestCase
{
    private function resolver(FakeUserScope $scope, int $limit = 200): DialogPeerResolver
    {
        return new DialogPeerResolver(new LazyUserScope($scope->resolver()), $limit);
    }

    private function dialogs(): array
    {
        return [
            '_' => 'messages.dialogs',
            'users' => [
                ['_' => 'user', 'id' => 4242, 'access_hash' => 987654321, 'username' => 'Ops_Team'],
                ['_' => 'user', 'id' => 99, 'access_hash' => 0, 'username' => 'hashless'],
            ],
            'chats' => [
                ['_' => 'chat', 'id' => -1001, 'access_hash' => 55555, 'title' => 'alerts'],
                ['_' => 'chat', 'id' => 77, 'title' => 'a basic group'],
            ],
        ];
    }

    public function test_nothing_is_dialled_at_construction(): void
    {
        $scope = new FakeUserScope;
        $resolver = $this->resolver($scope);

        self::assertFalse($resolver->resolved());
        self::assertSame(0, $scope->resolutions);
        self::assertSame([], $scope->calls);
    }

    public function test_a_numeric_id_resolves_through_the_dialog_list(): void
    {
        $scope = new FakeUserScope;
        $scope->replyWith($this->dialogs());

        $peer = ($this->resolver($scope))(4242);

        self::assertSame('inputPeerUser', $peer['_']);
        self::assertSame(4242, $peer['user_id']);
        self::assertSame('987654321', $peer['access_hash']);
    }

    public function test_a_supergroup_row_becomes_a_channel_peer_with_its_hash(): void
    {
        $scope = new FakeUserScope;
        $scope->replyWith($this->dialogs());

        $peer = ($this->resolver($scope))(-1001);

        self::assertSame('inputPeerChannel', $peer['_'], 'a chats row WITH a hash is a supergroup, not a basic group');
        self::assertSame(-1001, $peer['channel_id']);
        self::assertSame('55555', $peer['access_hash']);
    }

    public function test_a_basic_group_row_becomes_a_chat_peer_with_no_invented_hash(): void
    {
        $scope = new FakeUserScope;
        $scope->replyWith($this->dialogs());

        $peer = ($this->resolver($scope))(77);

        self::assertSame('inputPeerChat', $peer['_']);
        self::assertArrayNotHasKey('access_hash', $peer, 'basic groups take no hash, so none is fabricated');
    }

    public function test_a_username_resolves_case_insensitively_with_or_without_the_at_sign(): void
    {
        foreach (['@Ops_Team', 'ops_team'] as $spelling) {
            $scope = new FakeUserScope;
            $scope->replyWith($this->dialogs());

            $peer = ($this->resolver($scope))($spelling);

            self::assertSame('inputPeerUser', $peer['_'], sprintf('"%s" must resolve the same peer', $spelling));
            self::assertSame(4242, $peer['user_id']);
        }
    }

    public function test_the_dialog_list_is_fetched_once_and_memoized(): void
    {
        $scope = new FakeUserScope;
        $scope->replyWith($this->dialogs());
        $resolver = $this->resolver($scope);

        $resolver(4242);
        $resolver(4242);
        $resolver('@ops_team');

        self::assertSame(['messages.getDialogs'], $scope->methods(), 'one discovery call serves every later send');
        self::assertSame(1, $scope->resolutions, 'and the account scope itself resolved once');
        self::assertTrue($resolver->resolved());
    }

    public function test_the_configured_dialog_limit_rides_the_request(): void
    {
        $scope = new FakeUserScope;
        $scope->replyWith($this->dialogs());

        ($this->resolver($scope, 7))(4242);

        self::assertSame(7, $scope->onlyParams()['limit']);
    }

    public function test_a_user_row_with_no_access_hash_is_dropped_rather_than_sent_with_zero(): void
    {
        $scope = new FakeUserScope;
        $scope->replyWith($this->dialogs());

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('cannot resolve the MTProto peer for chat 99');

        // A zero hash is a guess with extra steps; the row must not index.
        ($this->resolver($scope))(99);
    }

    public function test_a_chat_the_account_has_never_opened_refuses_and_names_it(): void
    {
        $scope = new FakeUserScope;
        $scope->replyWith($this->dialogs());

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('cannot resolve the MTProto peer for chat 31337');

        ($this->resolver($scope))(31337);
    }

    public function test_an_unknown_username_refuses_loudly(): void
    {
        $scope = new FakeUserScope;
        $scope->replyWith($this->dialogs());

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('no @username by that name is among the account\'s dialogs');

        ($this->resolver($scope))('@nobody');
    }

    public function test_a_chat_that_is_neither_an_id_nor_a_username_refuses_loudly(): void
    {
        $scope = new FakeUserScope;
        $scope->replyWith($this->dialogs());

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('neither a numeric id nor an @username');

        ($this->resolver($scope))(['not', 'a', 'chat']);
    }

    public function test_a_null_chat_refuses_and_says_so(): void
    {
        $scope = new FakeUserScope;
        $scope->replyWith($this->dialogs());

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('chat (none)');

        ($this->resolver($scope))(null);
    }

    public function test_a_reply_with_no_peer_collections_refuses_rather_than_inventing(): void
    {
        $scope = new FakeUserScope;
        $scope->replyWith(['_' => 'messages.dialogs']);

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('cannot resolve the MTProto peer');

        ($this->resolver($scope))(4242);
    }

    public function test_a_failed_dialog_lookup_propagates_and_is_not_swallowed(): void
    {
        $scope = new FakeUserScope;
        $scope->failWith(new NotifyException('upstream is down'));

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('upstream is down');

        ($this->resolver($scope))(4242);
    }

    public function test_a_scope_that_cannot_be_resolved_refuses_before_any_lookup(): void
    {
        $resolver = new DialogPeerResolver(new LazyUserScope(static fn (): string => 'not-a-scope'));

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('not an object');

        $resolver(4242);
    }

    public function test_an_unbound_scope_refuses_with_the_install_level_message(): void
    {
        $resolver = new DialogPeerResolver(new LazyUserScope);

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('no MTProto user scope bound');

        $resolver(4242);
    }
}
