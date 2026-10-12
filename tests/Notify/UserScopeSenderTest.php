<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Core\Types\InputPeer;
use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use MeRezaRezaei\Teleframe\Notify\NotificationSender;
use MeRezaRezaei\Teleframe\Notify\NotifyException;
use MeRezaRezaei\Teleframe\Notify\UserScopeTransport;
use PHPUnit\Framework\TestCase;

/**
 * The MTProto user-scope sender (V1.1's first deferred item).
 *
 * Every assertion goes through an INJECTED seam — a stub scope, a stub peer
 * resolver, a stub uploader — never through whatever the machine happens
 * to have. There is no extension here, no network, no session string and no
 * credential: the transport is handed a scope the same way
 * `RelayFactory`/`RelaySessionResolver` hand one to `RelayTerminal`, and the
 * scope is a recording double. That is the point the CI pipeline #203/#204
 * lessons insist on: a test that lets the ENVIRONMENT pick its branch is a
 * rumour, so the "dial" happens against a double whose calls the test
 * asserts by name.
 */
final class UserScopeSenderTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir().'/tframe-notify-userscope-'.bin2hex(random_bytes(4));
        mkdir($this->tmp, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmp.'/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        @rmdir($this->tmp);
    }

    public function test_nothing_is_dialled_at_construction(): void
    {
        $scope = new FakeUserScope;
        $transport = new UserScopeTransport($scope->resolver());

        // The resolver has not run, so no session exists to have dialled.
        self::assertFalse($transport->resolved());
        self::assertSame(0, $scope->resolutions);
        self::assertSame([], $scope->calls);
        self::assertSame('mtproto:user-scope', $transport->describe());
    }

    public function test_the_scope_resolves_lazily_once_and_is_memoized(): void
    {
        $scope = new FakeUserScope;
        $transport = new UserScopeTransport($scope->resolver(), peerResolver: static fn (): array => InputPeer::chat(7));

        self::assertFalse($transport->resolved());

        $transport->call('sendMessage', ['chat_id' => 7, 'text' => 'first']);
        self::assertTrue($transport->resolved());
        self::assertSame(1, $scope->resolutions);

        $transport->call('sendMessage', ['chat_id' => 7, 'text' => 'second']);
        self::assertSame(1, $scope->resolutions, 'the scope is resolved once, not per send');
        self::assertSame(['messages.sendMessage', 'messages.sendMessage'], $scope->methods());
    }

    public function test_send_message_is_translated_to_the_mtproto_wire_shape(): void
    {
        $scope = new FakeUserScope;
        $transport = new UserScopeTransport($scope->resolver());

        $transport->call('sendMessage', [
            'chat_id' => InputPeer::chat(7),
            'text' => '[ALERT] app.alert',
            'message_thread_id' => 5,
            'entities' => [['_' => 'messageEntityBold', 'offset' => 0, 'length' => 3]],
        ]);

        $params = $scope->onlyParams();
        self::assertSame('messages.sendMessage', $scope->methods()[0]);
        self::assertSame(7, $params['peer']['chat_id']);
        self::assertSame('[ALERT] app.alert', $params['message']);
        self::assertArrayNotHasKey('reply_to_msg_id', $params, 'the declared constructor has NO such field — sending it is the silent no-op this test used to bless');
        self::assertArrayHasKey('reply_to', $params, 'the topic rides the declared reply_to:flags.0?InputReplyTo field');
        self::assertSame('inputReplyToMessage', $params['reply_to']['_']);
        self::assertSame(5, $params['reply_to']['reply_to_msg_id'], 'the topic id is the reply target, one level down inside the InputReplyTo');
        self::assertSame(0, $params['entities'][0]['offset']);
        self::assertGreaterThan(0, $params['random_id']);
        self::assertArrayNotHasKey('message_thread_id', $params, 'bot-api params never leak onto the wire');
    }

    public function test_a_ready_input_peer_array_is_used_as_is(): void
    {
        $scope = new FakeUserScope;
        $peerResolverFired = 0;
        $transport = new UserScopeTransport(
            $scope->resolver(),
            peerResolver: static function () use (&$peerResolverFired): array {
                $peerResolverFired++;

                return InputPeer::user(1, 'abc');
            },
        );

        $transport->call('sendMessage', ['chat_id' => InputPeer::channel(-1001, 'hash'), 'text' => 'x']);

        self::assertSame(0, $peerResolverFired, 'a ready peer needs no resolver');
        self::assertSame('inputPeerChannel', $scope->onlyParams()['peer']['_']);
    }

    public function test_a_bare_chat_id_goes_through_the_injected_peer_resolver(): void
    {
        $scope = new FakeUserScope;
        $seen = [];
        $transport = new UserScopeTransport(
            $scope->resolver(),
            peerResolver: static function (mixed $chat) use (&$seen): array {
                $seen[] = $chat;

                return InputPeer::channel(-1001, 'hash');
            },
        );

        $transport->call('sendMessage', ['chat_id' => -1001, 'text' => 'x']);

        self::assertSame([-1001], $seen);
        self::assertSame('inputPeerChannel', $scope->onlyParams()['peer']['_']);
    }

    public function test_an_attachment_send_uploads_and_ships_media(): void
    {
        $scope = new FakeUserScope;
        $uploaded = [];
        $file = $this->tmp.'/report.html';
        file_put_contents($file, '<h1>report</h1>');

        $transport = new UserScopeTransport(
            $scope->resolver(),
            peerResolver: static fn (): array => InputPeer::chat(7),
            uploader: static function (string $path, string $mime, string $name) use (&$uploaded): array {
                $uploaded[] = [$path, $mime, $name];

                return ['_' => 'inputMediaUploadedDocument', 'mime_type' => $mime];
            },
        );

        $transport->call('sendDocument', [
            'chat_id' => 7,
            'document' => $file,
            'mime' => 'text/html',
            'caption' => '[NOTICE] report',
        ]);

        self::assertSame([[$file, 'text/html', 'report.html']], $uploaded);
        self::assertSame('messages.sendMedia', $scope->methods()[0]);
        self::assertSame('[NOTICE] report', $scope->onlyParams()['message']);
        self::assertSame('inputMediaUploadedDocument', $scope->onlyParams()['media']['_']);
    }

    public function test_an_unconfigured_scope_refuses_loudly_on_first_use(): void
    {
        $transport = new UserScopeTransport;

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('no MTProto user scope bound');

        $transport->call('sendMessage', ['chat_id' => 7, 'text' => 'x']);
    }

    public function test_an_unconfigured_transport_says_so_in_its_label(): void
    {
        self::assertSame('mtproto:user-scope (unconfigured)', (new UserScopeTransport)->describe());
    }

    public function test_the_shared_sender_refuses_loudly_on_an_unconfigured_user_scope(): void
    {
        $sender = new NotificationSender(
            new MessageCompiler($this->tmp.'/tpl', $this->tmp.'/cache'),
            transport: new UserScopeTransport,
        );

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('no MTProto user scope bound');

        $sender->send('hi', ['chat' => 7]);
    }

    public function test_a_resolver_that_answers_nothing_usable_refuses_loudly(): void
    {
        $transport = new UserScopeTransport(static fn (): string => 'not-a-scope');

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('not an object');

        $transport->call('sendMessage', ['chat_id' => 7, 'text' => 'x']);
    }

    public function test_a_scope_without_call_refuses_loudly(): void
    {
        $transport = new UserScopeTransport(static fn (): object => new \stdClass);

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('does not expose call(string, array)');

        $transport->call('sendMessage', ['chat_id' => 7, 'text' => 'x']);
    }

    public function test_a_bare_chat_id_with_no_peer_resolver_refuses_loudly(): void
    {
        $scope = new FakeUserScope;
        $transport = new UserScopeTransport($scope->resolver());

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('cannot address the MTProto peer');

        $transport->call('sendMessage', ['chat_id' => -1001, 'text' => 'x']);
    }

    public function test_a_peer_resolver_answering_junk_refuses_loudly(): void
    {
        $scope = new FakeUserScope;
        $transport = new UserScopeTransport($scope->resolver(), peerResolver: static fn (): array => ['nope' => true]);

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('not an InputPeer');

        $transport->call('sendMessage', ['chat_id' => -1001, 'text' => 'x']);
    }

    public function test_an_attachment_with_no_uploader_refuses_loudly_rather_than_losing_the_report(): void
    {
        $scope = new FakeUserScope;
        $transport = new UserScopeTransport($scope->resolver(), peerResolver: static fn (): array => InputPeer::chat(7));

        try {
            $transport->call('sendDocument', ['chat_id' => 7, 'document' => $this->tmp.'/x.html', 'caption' => 'c']);
            self::fail('an attachment send with no uploader must refuse');
        } catch (NotifyException $e) {
            self::assertStringContainsString('needs an uploader seam', $e->getMessage());
        }

        self::assertSame([], $scope->calls, 'nothing was dialled before the refusal');
    }

    public function test_an_uncarried_method_refuses_loudly(): void
    {
        $scope = new FakeUserScope;
        $transport = new UserScopeTransport($scope->resolver());

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('carries sendMessage/sendDocument only');

        $transport->call('getMe');
    }

    public function test_the_shared_sender_uses_the_user_scope_transport(): void
    {
        $scope = new FakeUserScope;
        $scope->replyWith(['_' => 'updates']);

        $sender = new NotificationSender(
            new MessageCompiler($this->tmp.'/tpl', $this->tmp.'/cache'),
            transport: new UserScopeTransport($scope->resolver(), peerResolver: static fn (): array => InputPeer::chat(-1001, 'h')),
        );

        $receipt = $sender->sendWithReceipt('[ALERT] boom', ['chat' => -1001, 'thread_id' => 4]);

        self::assertSame('mtproto:user-scope', $receipt->transport);
        self::assertSame('sendMessage', $receipt->method);
        self::assertSame(['_' => 'updates'], $receipt->response);
        self::assertFalse($receipt->rateLimited());
        self::assertSame(['messages.sendMessage'], $scope->methods());
        self::assertStringContainsString('[ALERT] boom', $scope->onlyParams()['message']);
        self::assertSame(4, $scope->onlyParams()['reply_to']['reply_to_msg_id']);
        self::assertSame('inputReplyToMessage', $scope->onlyParams()['reply_to']['_']);
        self::assertSame(1, $scope->resolutions);
    }

    public function test_send_still_returns_the_raw_transport_reply(): void
    {
        $scope = new FakeUserScope;
        $scope->replyWith(['updates' => 1]);

        $sender = new NotificationSender(
            new MessageCompiler($this->tmp.'/tpl', $this->tmp.'/cache'),
            transport: new UserScopeTransport($scope->resolver(), peerResolver: static fn (): array => InputPeer::chat(7)),
        );

        self::assertSame(['updates' => 1], $sender->send('hi', ['chat' => 7]));
    }

    public function test_a_sender_with_neither_bot_nor_transport_refuses_loudly_on_send(): void
    {
        $sender = new NotificationSender(new MessageCompiler($this->tmp.'/tpl', $this->tmp.'/cache'));

        self::assertSame('none', $sender->transportName(), 'the sender is constructible and says it is unconfigured');

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('no transport');

        $sender->send('hi', ['chat' => 7]);
    }

    public function test_the_bot_path_is_unchanged_when_no_transport_is_given(): void
    {
        $bot = new FakeBotClient;
        $sender = new NotificationSender(new MessageCompiler($this->tmp.'/tpl', $this->tmp.'/cache'), $bot);

        self::assertSame('bot-api', $sender->transportName());

        $sender->send('hi', ['chat' => 42, 'thread_id' => 3]);

        self::assertSame('sendMessage', $bot->calls[0]['method']);
        self::assertSame(42, $bot->calls[0]['params']['chat_id']);
        self::assertSame(3, $bot->calls[0]['params']['message_thread_id']);
    }
}
