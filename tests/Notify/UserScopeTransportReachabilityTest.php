<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Laravel\NotifyServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use MeRezaRezaei\Teleframe\Notify\Level;
use MeRezaRezaei\Teleframe\Notify\NotificationSender;
use MeRezaRezaei\Teleframe\Notify\NotificationTransportContract;
use MeRezaRezaei\Teleframe\Notify\NotifyCommand;
use MeRezaRezaei\Teleframe\Notify\NotifyException;
use MeRezaRezaei\Teleframe\Notify\UserScopeTransport;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * THE REACHABILITY PROOF — the test whose absence WAS the finding.
 *
 * `UserScopeTransport` shipped as a first-class MTProto user sender with no
 * config key, no import and no binding anywhere in the package: before this
 * file, `grep -rn UserScopeTransport src/` returned exactly one hit, its own
 * `class` line. Its unit test built it against a fake directly, so it proved
 * TRANSLATION while proving nothing about whether any install could REACH
 * it. A capability that a shipped install cannot select is not a capability.
 *
 * So every test here starts from a CONFIG VALUE and ends on a dialled
 * `messages.sendMessage` on the user path — through the container, through
 * the shipped peer resolver, with the `access_hash` obtained from a real
 * `messages.getDialogs` reply. Remove the provider binding and this file
 * goes red; that is the point of it.
 *
 * The terminal is a recording double, not a socket: this repo has never run
 * a user-scope notify send against live Telegram, and a test that dialled
 * one would be a credential in CI, not evidence.
 */
final class UserScopeTransportReachabilityTest extends TestbenchTestCase
{
    private string $tmp;

    private FakeUserScope $scope;

    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [NotifyServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmp = sys_get_temp_dir().'/tframe-notify-reach-'.bin2hex(random_bytes(4));
        mkdir($this->tmp, 0777, true);

        $this->scope = new FakeUserScope;

        $this->bindOfflineHost();

        config([
            // deterministic delivery for the test; not part of the claim
            'teleframe.notify.queue' => 'sync',
            'teleframe.notify.budgets.enforce' => false,
        ]);
    }

    /**
     * Exactly what a host with an MTProto session has: a message compiler and
     * the core provider's own `TeleframeClient`, which the notify provider
     * reaches for. NOTHING host-only — no seam binding, no hand-built
     * transport. A config value alone has to finish the job, which is the
     * entire claim of this file.
     *
     * Re-callable: a test that needs a different app state calls
     * `refreshApplication()` and then this.
     */
    private function bindOfflineHost(): void
    {
        $this->scope = new FakeUserScope;

        $this->app->singleton(MessageCompiler::class, fn (): MessageCompiler => new MessageCompiler($this->tmp.'/tpl', $this->tmp.'/cache'));
        $this->app->bind(TeleframeClient::class, fn (): object => new class($this->scope)
        {
            public function __construct(private readonly FakeUserScope $scope) {}

            public function user(): object
            {
                return $this->scope;
            }
        });
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmp.'/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        @rmdir($this->tmp);

        parent::tearDown();
    }

    /**
     * A dialog reply in the shape `messages.getDialogs` actually returns:
     * `users` and `chats` rows, each carrying the `access_hash` that MTProto
     * refuses to let a bare id skip.
     */
    private function dialogs(): array
    {
        return [
            '_' => 'messages.dialogs',
            'users' => [
                ['_' => 'user', 'id' => 4242, 'access_hash' => 987654321, 'username' => 'ops'],
            ],
            'chats' => [
                ['_' => 'chat', 'id' => -1001, 'access_hash' => 55555, 'title' => 'alerts'],
                ['_' => 'chat', 'id' => 77, 'title' => 'a basic group'],
            ],
            'dialogs' => [],
        ];
    }

    public function test_the_shipped_default_is_the_bot_transport(): void
    {
        $transport = $this->app->make(NotificationTransportContract::class);

        self::assertSame('bot-api', $transport->describe(), 'the config default is unchanged: bot');
        self::assertSame(
            'bot',
            config('teleframe.notify.transport'),
            'the shipped key defaults to bot, so no existing install changes behaviour',
        );
    }

    public function test_a_config_value_alone_reaches_a_real_send_on_the_user_path(): void
    {
        config([
            'teleframe.notify.transport' => 'user_scope',
            'teleframe.notify.default_chat' => '@ops',
        ]);

        $this->scope->replyWith($this->dialogs())->replyWith(['_' => 'updates']);

        $sender = $this->app->make(NotificationSender::class);

        self::assertInstanceOf(UserScopeTransport::class, $this->transport());
        self::assertSame('mtproto:user-scope', $sender->transportName());

        $sender->send('[ALERT] backup did not come in time', ['chat' => '@ops', 'thread_id' => 3]);

        self::assertSame(['messages.getDialogs', 'messages.sendMessage'], $this->scope->methods());

        $sent = $this->lastParams();
        self::assertSame('inputPeerUser', $sent['peer']['_'], 'the peer was resolved from the account dialogs, not guessed');
        self::assertSame(4242, $sent['peer']['user_id']);
        self::assertSame('987654321', $sent['peer']['access_hash']);
        self::assertStringContainsString('[ALERT] backup did not come in time', $sent['message']);
        self::assertArrayNotHasKey('reply_to_msg_id', $sent, 'undeclared field: it would be dropped by the encoder and the topic would land in General');
        self::assertSame('inputReplyToMessage', $sent['reply_to']['_']);
        self::assertSame(3, $sent['reply_to']['reply_to_msg_id'], 'the topic id rides as the MTProto reply target');
    }

    public function test_the_whole_notify_pipeline_sends_as_the_user_account_from_config_alone(): void
    {
        config([
            'teleframe.notify.transport' => 'user_scope',
            'teleframe.notify.topics' => [
                ['name' => 'backup', 'level' => 'error', 'chat' => '@ops', 'thread_id' => 7],
            ],
        ]);

        $this->scope->replyWith($this->dialogs())->replyWith(['_' => 'updates']);

        $service = $this->app->make(NotifyCommand::SERVICE_KEY);
        $service->log(Level::Error, 'backup did not come in time', [], 'backup');

        self::assertSame(
            ['messages.getDialogs', 'messages.sendMessage'],
            $this->scope->methods(),
            'the topic row chat, the peer lookup and the send all happened on the user path',
        );

        foreach ($this->scope->calls as $call) {
            if ($call['method'] === 'messages.sendMessage') {
                self::assertSame(4242, $call['params']['peer']['user_id']);
            }
        }
    }

    public function test_a_numeric_topic_chat_resolves_to_the_channel_peer_with_its_hash(): void
    {
        config(['teleframe.notify.transport' => 'user_scope']);

        $this->scope->replyWith($this->dialogs())->replyWith(['_' => 'updates']);

        $this->app->make(NotificationSender::class)->send('alert', ['chat' => -1001]);

        $sent = $this->lastParams();
        self::assertSame('inputPeerChannel', $sent['peer']['_'], 'a supergroup row carries a hash, so it is a channel peer');
        self::assertSame(-1001, $sent['peer']['channel_id']);
        self::assertSame('55555', $sent['peer']['access_hash']);
    }

    public function test_a_basic_group_row_resolves_without_inventing_a_hash(): void
    {
        config(['teleframe.notify.transport' => 'user_scope']);

        $this->scope->replyWith($this->dialogs())->replyWith(['_' => 'updates']);

        $this->app->make(NotificationSender::class)->send('alert', ['chat' => 77]);

        $sent = $this->lastParams();
        self::assertSame('inputPeerChat', $sent['peer']['_'], 'basic groups take no access_hash, so none is fabricated');
        self::assertSame(77, $sent['peer']['chat_id']);
    }

    public function test_a_chat_the_account_has_never_opened_throws_rather_than_guessing_a_hash(): void
    {
        config(['teleframe.notify.transport' => 'user_scope']);

        $this->scope->replyWith($this->dialogs());

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('cannot resolve the MTProto peer for chat 31337');

        $this->app->make(NotificationSender::class)->send('alert', ['chat' => 31337]);
    }

    public function test_a_user_scope_with_no_session_is_inert_and_says_so_then_refuses(): void
    {
        // A fresh app with NEITHER a TeleframeClient nor a USER_SCOPE_KEY:
        // the honest bare host that turned the key on anyway.
        $this->refreshApplication();
        $this->app->singleton(MessageCompiler::class, fn (): MessageCompiler => new MessageCompiler($this->tmp.'/tpl', $this->tmp.'/cache'));
        config(['teleframe.notify.transport' => 'user_scope']);

        $transport = $this->app->make(NotificationTransportContract::class);

        self::assertSame(
            'mtproto:user-scope (unconfigured)',
            $transport->describe(),
            'a status line must never imply a live account when there is none',
        );

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('no MTProto user scope bound');

        $transport->call('sendMessage', ['chat_id' => 4242, 'text' => 'alert']);
    }

    /**
     * The negative half of the claim: no arrangement of the user-scope
     * sender produces a quiet return. Each row is a way an operator can end
     * up with this key on and nothing working. Row 1 must REACH THE WIRE;
     * rows 2 and 3 must REFUSE, and each refusal must be a NotifyException
     * with a message that says what to fix. A return that is neither is the
     * silent no-op, and that is what this test exists to make impossible.
     */
    public function test_no_configuration_of_the_user_scope_sender_is_a_silent_no_op(): void
    {
        // Row 1 — the happy path reaches the wire.
        $this->bindOfflineHost();
        config(['teleframe.notify.transport' => 'user_scope']);
        $this->scope->replyWith($this->dialogs())->replyWith(['_' => 'updates']);

        $this->app->make(NotificationSender::class)->send('probe', ['chat' => 4242]);

        self::assertContains('messages.sendMessage', $this->scope->methods(), 'a resolvable chat must actually send');

        // Row 2 — an unresolvable chat refuses, loudly and specifically.
        $this->refreshApplication();
        $this->bindOfflineHost();
        config(['teleframe.notify.transport' => 'user_scope']);
        $this->scope->replyWith(['_' => 'messages.dialogs', 'users' => [], 'chats' => []]);

        try {
            $this->app->make(NotificationSender::class)->send('probe', ['chat' => 4242]);
            self::fail('an unresolvable chat must refuse, not quietly return');
        } catch (NotifyException $e) {
            self::assertStringContainsString('cannot resolve the MTProto peer', $e->getMessage());
        }

        self::assertSame(['messages.getDialogs'], $this->scope->methods(), 'it refused BEFORE dialling a send');

        // Row 3 — no session at all refuses at the scope seam.
        $this->refreshApplication();
        $this->app->singleton(MessageCompiler::class, fn (): MessageCompiler => new MessageCompiler($this->tmp.'/tpl', $this->tmp.'/cache'));
        config(['teleframe.notify.transport' => 'user_scope']);

        try {
            $this->app->make(NotificationSender::class)->send('probe', ['chat' => 4242]);
            self::fail('a sender with no user scope must refuse, not quietly return');
        } catch (NotifyException $e) {
            self::assertStringContainsString('no MTProto user scope bound', $e->getMessage());
        }
    }

    public function test_an_unknown_transport_driver_is_a_loud_error_not_a_fallback(): void
    {
        config(['teleframe.notify.transport' => 'mtproto']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('expected bot|user_scope');

        $this->app->make(NotificationTransportContract::class);
    }

    public function test_a_host_seam_overrides_the_shipped_peer_resolver_verbatim(): void
    {
        config(['teleframe.notify.transport' => 'user_scope']);

        $this->app->bind(NotifyServiceProvider::USER_PEER_RESOLVER_KEY, static fn (): callable => static fn (mixed $chat): array => [
            '_' => 'inputPeerChannel',
            'channel_id' => -4242,
            'access_hash' => '777',
        ]);

        $this->scope->replyWith(['_' => 'updates']);

        $this->app->make(NotificationSender::class)->send('alert', ['chat' => '@someone-never-opened']);

        self::assertSame(['messages.sendMessage'], $this->scope->methods(), 'the host resolver answered, so no dialog lookup happened');
        self::assertSame(-4242, $this->lastParams()['peer']['channel_id']);
    }

    public function test_the_scope_is_not_resolved_until_the_first_send(): void
    {
        config(['teleframe.notify.transport' => 'user_scope']);

        $this->app->make(NotificationTransportContract::class);
        $this->app->make(NotificationSender::class);

        self::assertSame(0, $this->scope->resolutions, 'a config value alone must not dial Telegram from a container factory');
        self::assertSame([], $this->scope->calls);
    }

    /**
     * The params of the LAST dialled call. `FakeUserScope::onlyParams()`
     * insists on exactly one call, which is the wrong tool once the shipped
     * peer resolver has made the dialog lookup that precedes every send.
     *
     * @return array<string, mixed>
     */
    private function lastParams(): array
    {
        $calls = $this->scope->calls;
        self::assertNotSame([], $calls, 'nothing was dialled');

        $last = $calls[count($calls) - 1];

        return $last['params'];
    }

    private function transport(): NotificationTransportContract
    {
        $transport = $this->app->make(NotificationTransportContract::class);
        self::assertInstanceOf(NotificationTransportContract::class, $transport);

        return $transport;
    }
}
