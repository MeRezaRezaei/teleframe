#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * examples/ai-read-and-reply.php — THE owner's sentence, end to end:
 *
 *     "connect my ai to read my telegram messages and answer them
 *      … just give me a facade in codes"
 *
 * One declarative app, declared through the one facade, that
 *   (1) READS a chat's messages and
 *   (2) ANSWERS each new message,
 *   and that is installed by the provider boot in EVERY consuming process
 *   and routes on the REAL constructor name, not only the `*` catch-all.
 *
 * Run it (from the repository root, after `composer install`):
 *
 *   # offline: the whole chain against a fake transport — no credentials,
 *   # no network, no session, no database. This is the acceptance run.
 *   php examples/ai-read-and-reply.php --offline
 *
 *   # live: real MTProto. Credentials come from the ENVIRONMENT only.
 *   TG_API_ID=… TG_API_HASH=… TELEFRAME_SESSION='base64…' \
 *   TG_PEER=@somebody php examples/ai-read-and-reply.php
 *
 * In a Laravel host steps (1) and (2) are three lines in a service provider
 * and NOTHING else — the app then fires under `php artisan teleframe:daemon`:
 *
 *     TelegramApp::define('ai-assistant', AppMode::User, [
 *         AppTrigger::on('updateNewMessage', [AiAssistant::class, 'onMessage']),
 *     ]);
 *
 * (The facade and AppRuntime are the same object graph: TelegramApp resolves
 * `teleclient.app.service`, an AppRuntime. This file builds one directly so
 * it runs with no Laravel boot; a host gets the identical call.)
 *
 * Credentials ruling (AGENTS.md): the session is read from the environment at
 * call time and never written anywhere. No message CONTENT is printed unless
 * you pass --show; the default path prints ids, constructors and counts only.
 */

require __DIR__.'/../vendor/autoload.php';

use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppRegistry;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\App\AppTrigger;
use MeRezaRezaei\Teleframe\Handler\HandlerMatcher;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Testing\FakeDispatcher;
use Psr\Container\ContainerInterface;

$offline = in_array('--offline', $argv, true);
$show = in_array('--show', $argv, true);

/* ───────────────────────────────────────────────────────────────────────────
 *  THE APP — a plain class, the way a host writes it. Identical handler
 *  shape to the facade's, so the offline and live runs exercise one code
 *  path. The runtime is injected so the class stays testable.
 * ─────────────────────────────────────────────────────────────────────────── */
final class AiAssistant
{
    /** @var list<int> ids of the messages the assistant read */
    public static array $read = [];

    /** @var list<array{account: int, peer: mixed, bytes: int}> */
    public static array $answered = [];

    public function __construct(private readonly AppRuntime $runtime) {}

    /**
     * The trigger handler — the substrate's signature,
     * `function (Update $u): void`, DI-resolved by the dispatcher.
     */
    public function onMessage(Update $update): void
    {
        $message = $update->array['message'] ?? null;
        if (! is_array($message)) {
            return;
        }

        $text = (string) ($message['message'] ?? '');
        $peer = $message['peer_id'] ?? null;
        if ($text === '' || $peer === null) {
            return;
        }

        // (1) READ — the conversation, so the answer has context.
        foreach ($this->runtime->read($peer, 20) as $row) {
            if (is_array($row) && isset($row['id'])) {
                self::$read[] = (int) $row['id'];
            }
        }

        // (2) ANSWER — same account, same app, one call.
        $this->runtime->answer($update->accountId, $peer, self::draft($text));

        self::$answered[] = ['account' => $update->accountId, 'peer' => $peer, 'bytes' => strlen($text)];
    }

    /**
     * Where a real host puts its model call. A pure function of the text so
     * the example runs with no API key; a real assistant swaps THIS method
     * for an LLM client and nothing else changes.
     */
    public static function draft(string $text): string
    {
        return 'you said: '.$text;
    }
}

/* ───────────────────────────────────────────────────────────────────────────
 *  THE TRANSPORT — offline fakes it, live resolves the real client. This is
 *  the single seam the app framework hands an app: AppMode => scope.
 * ─────────────────────────────────────────────────────────────────────────── */

/** @var list<array{peer: mixed, text: string}> $sent */
$sent = [];

$transport = static fn (AppMode $mode): object => new class($sent)
{
    /** @param list<array{peer: mixed, text: string}> $sent */
    public function __construct(public array &$sent) {}

    /** READ: the history a real UserAccountScope::getHistory would return. */
    public function getHistory(int|string|array $peer, int $limit = 50, int $offsetId = 0): array
    {
        return ['messages' => [
            ['id' => 100, 'message' => 'earlier'],
            ['id' => 101, 'message' => 'context'],
        ]];
    }

    /** ANSWER: record instead of dialing. */
    public function sendMessage(int|string|array $peer, string $text, array $options = []): array
    {
        $this->sent[] = ['peer' => $peer, 'text' => $text];

        return ['id' => 202];
    }
};

/* ───────────────────────────────────────────────────────────────────────────
 *  WIRING — the facade call, verbatim from the TelegramApp docblock.
 * ─────────────────────────────────────────────────────────────────────────── */
$runtime = new AppRuntime(new AppRegistry, new NullSchedulePublisher, $transport);
$assistant = new AiAssistant($runtime);

$name = $runtime->define('ai-assistant', AppMode::User, [
    AppTrigger::on('updateNewMessage', static fn (Update $u) => $assistant->onMessage($u)),
], [
    AppCall::every('status-ping', 3600, 'messages.sendMessage', [
        'peer' => getenv('TG_PEER') ?: '@ops',
        'message' => 'ai-assistant alive',
    ]),
]);

echo "declared app: {$name}\n";
foreach ($runtime->list() as $row) {
    echo sprintf(
        "  %s (mode %s): %d trigger(s), %d call(s)\n",
        $row['name'],
        $row['mode'],
        $row['triggers'],
        $row['calls'],
    );
}
foreach (array_keys($runtime->calls()) as $callId) {
    echo "  scheduled: {$callId}\n";
}

/* ───────────────────────────────────────────────────────────────────────────
 *  PROOF — the trigger is live on the SHARED hot table and the matcher
 *  finds it by its real constructor name, not only by the `*` catch-all.
 *  (This is the assertion the framework's own test makes.)
 * ─────────────────────────────────────────────────────────────────────────── */
$match = (new HandlerMatcher(HandlerRegistry::instance()))->match('updateNewMessage');
echo 'matcher sees updateNewMessage: '.($match === null ? 'NO — the app would never fire' : 'yes')."\n";
if ($match === null) {
    exit(1);
}

/* ───────────────────────────────────────────────────────────────────────────
 *  PROOF — drive one update through the REAL pipeline (real registry, real
 *  onion, real echo eliminator, real replay dedup), through the shipped
 *  FakeDispatcher seam a host uses in its own tests.
 * ─────────────────────────────────────────────────────────────────────────── */
$fake = new FakeDispatcher(
    [[
        'update' => [
            '_' => 'updateNewMessage',
            'message' => [
                '_' => 'message',
                'id' => 7,
                'message' => 'ping',
                'peer_id' => ['_' => 'peerUser', 'user_id' => 501558149],
            ],
        ],
        'account_id' => 42,
    ]],
    HandlerRegistry::instance(),
    new class implements ContainerInterface
    {
        public function get(string $id): mixed
        {
            throw new RuntimeException("nothing to resolve for [{$id}] in this offline run");
        }

        public function has(string $id): bool
        {
            return false;
        }
    },
    new InMemoryCache,
);

$fake->run();

$dispatched = count($fake->dispatched);
echo 'pipeline dispatched: '.$dispatched.($dispatched === 1 ? '' : ' — the trigger never ran')."\n";
echo 'messages read: '.count(AiAssistant::$read).' (ids: '.implode(', ', AiAssistant::$read).")\n";
echo 'replies sent: '.count(AiAssistant::$answered)."\n";

foreach (AiAssistant::$answered as $reply) {
    // CONTENT prints only on explicit --show. The default shows the peer
    // reference and a byte count: a message body is the user's private data.
    echo $show
        ? '  answered peer='.json_encode($reply['peer']).' with "'.AiAssistant::draft('…')."\"\n"
        : '  answered peer='.json_encode($reply['peer']).' ('.$reply['bytes']." bytes of inbound text)\n";
}

if ($dispatched !== 1 || AiAssistant::$answered === []) {
    fwrite(STDERR, "\nFAILED: the declarative app did not read and answer.\n");
    exit(1);
}

/* ───────────────────────────────────────────────────────────────────────────
 *  LIVE — real credentials from the environment, one real dial. Bot mode
 *  cannot read history (the Bot API has no such method), so this is a
 *  USER-mode app by construction.
 * ─────────────────────────────────────────────────────────────────────────── */
echo "\n--- offline acceptance: PASSED (no credentials, no network, no session)\n";

if ($offline) {
    exit(0);
}

$peer = getenv('TG_PEER') ?: '';
if ($peer === '') {
    fwrite(STDERR, "Set TG_PEER (@user or chat id) for the live run, or pass --offline.\n");
    exit(1);
}

$live = new AppRuntime(new AppRegistry, new NullSchedulePublisher);
$history = $live->read($peer, 5);

echo 'live read of '.$peer.': '.count($history)." message(s)\n";
foreach ($history as $row) {
    // ids only — never the bodies.
    echo '  id='.(string) ($row['id'] ?? '?')."\n";
}

echo 'live answer: '.$live->answer(42, $peer, 'hello from examples/ai-read-and-reply.php')['id']."\n";
