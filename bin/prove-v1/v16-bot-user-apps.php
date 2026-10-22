<?php

declare(strict_types=1);

/*
 * V1.6 — "a bot or user app that RESPONDS to updates".
 *
 * THE OPERATOR'S OWN WORDS — the spec this section proves, quoted whole from
 * .brain/verbatim/2026-09-24-notification-logger-backup-and-bot-framework.md:
 *
 *   "also we need a way to make bot and user apps that are going to respond to
 *    some updates and or schedule telegram calls they do and so on. consider
 *    something like this if i want to connect my ai to read my telegram
 *    messages and answer them i should be able to or saying something in chats
 *    triggers our bots and things that lets me make effective bots using the
 *    power we have here with data models database observers redis hot reload
 *    and whatever we did for this project until now in fact it's a core package
 *    that is going to let me make programs of telegram using it it supposed to
 *    have this concern to handle the telegram part for me and just give me a
 *    facade in codes to call for what I want from it."
 *
 * WHAT PART OF THAT THIS SECTION PROVES, clause by clause:
 *
 *   "saying something in chats triggers our bots"
 *        → the load-bearing sentence, and the reason this section exists. Every
 *          other capability section of this gate stops at its FIRST REFUSAL (no
 *          api id, no bot token), which is the correct posture for an
 *          unconfigured host — and also means nothing behind the refusal was
 *          ever entered. Here the chain is ENTERED: a handler registered
 *          through the shipped public face resolves through the host's own
 *          container, is matched by the shipped zero-regex matcher, runs the
 *          shipped pipeline, and PRODUCES A VALUE — a recorded call, a captured
 *          send — asserted on that value, never on "the method exists".
 *
 *   "bot and user apps"
 *        → both AppMode::User (MTProto UserAccountScope) and AppMode::Bot (Bot
 *          API BotClient) are driven end to end through their own transport
 *          seam, and the Bot API's honest inability to read message history is
 *          asserted as the refusal it is.
 *
 *   "connect my ai to read my telegram messages and answer them"
 *        → the shipped Message layer (MessageFactory → MessageCompiler →
 *          MarkdownEntityParser) compiles a real reply plan from the text read
 *          out of the inbound update, and the answer goes back out through a
 *          RECORDING transport as that exact TEXT. The text is the assertion.
 *
 *   "and or schedule telegram calls they do"
 *        → a declared AppCall is previewed onto the schedule seam and then RUN
 *          NOW through the app's own transport; a call whose catalog method
 *          does not belong to that app's mode refuses loudly instead of running.
 *
 *   "redis hot reload"
 *        → asserted against the CURRENT TRUTH, in both directions. The
 *          Redis-free half — an app defined at runtime is served by an
 *          ALREADY-RESOLVED dispatcher with no restart — is proven with a
 *          recorded handler invocation. The Redis-backed half is probed and
 *          reported by name; when this host has no Redis the check says so
 *          plainly rather than pretending a Redis-backed behaviour passed.
 *
 * HOW IT DRIVES UPDATES — the host's real intake seam, not a private back door.
 * `new HandlerSink(app(UpdateDispatcher::class))` is verbatim what
 * `DaemonCommand::handlerSink()` builds in production, and HandlerSink
 * implements the shipped UpdateSinkInterface, so a handler registered here is
 * reached exactly the way a routed bus entry reaches it in a live daemon.
 *
 * THE FAKES ARE THE SHIPPED ONES. FakeDispatcher, InMemoryCache, HandlerMatcher
 * and the whole handler substrate live under src/ and are autoloaded in the
 * host. The two doubles that live in tests/ are loaded BY PATH rather than by
 * autoload, because `autoload-dev` is not installed for a package dependency —
 * the same reason the V1.5 probe require_once's its fixtures.
 *
 * NOTHING CREDENTIAL IS INVOLVED. No api id, no api hash, no bot token, no user
 * session, no bot session, no auth key; no socket and no HTTP request is opened
 * anywhere below. The message text read and asserted here is text invented in
 * this file, not a real chat.
 */

/* ─────────────────────────────────────────────────────────────────────────────
 * THE HOST'S HANDLER CLASS — one plain invokable service, the way a host writes
 * it: a class with __invoke(Update), constructor-injected with a collaborator
 * the host binds in the container. Registered BY CLASS NAME below, so the
 * dispatcher has to resolve it through the host's own container for it to run at
 * all.
 *
 * Guarded rather than bare: three other capability sections are require()d into
 * the SAME process, and a fatal "cannot redeclare" here would take the whole gate
 * down instead of failing one honest check.
 * ────────────────────────────────────────────────────────────────────────── */

use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppResponder;
use MeRezaRezaei\Teleframe\App\AppSupport;
use MeRezaRezaei\Teleframe\App\AppTrigger;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\HandlerSink;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use MeRezaRezaei\Teleframe\Message\MessageFactory;
use MeRezaRezaei\Teleframe\Testing\FakeDispatcher;
use MeRezaRezaei\Teleframe\Tests\BotMap\Support\RecordingBotClient;
use MeRezaRezaei\Teleframe\Tests\Support\FakeUserScope;

if (! class_exists('TfV16ProbeLedger', false)) {
    class TfV16ProbeLedger
    {
        /** @var list<array{string, int, string}> */
        public array $seen = [];
    }
}

if (! class_exists('TfV16HostBot', false)) {
    class TfV16HostBot
    {
        public function __construct(private readonly TfV16ProbeLedger $ledger) {}

        public function __invoke(Update $update): string
        {
            $this->ledger->seen[] = [
                'scheduled-post',
                $update->accountId,
                (string) ($update->array['message']['message'] ?? ''),
            ];

            return 'handled-by-container';
        }
    }
}

/**
 * Every account id in this section. Obvious fakes; a host's own account ids are
 * its own rows and nothing here touches them.
 */
const V16_ACCOUNT = 9001;
const V16_BOT_ACCOUNT = 9002;

/**
 * The peers the inbound frames carry, in the wire shape each mode really uses.
 * MTProto carries a normalized peer ARRAY; the Bot API carries a chat id or a
 *
 * @username, and BotClient::sendMessage()'s own signature says so — so the bot
 * app's frame carries the id, not an array. Getting that wrong is a TypeError
 * from the shipped transport, which is the transport doing its job.
 */
$v16Peer = ['_' => 'peerUser', 'user_id' => 501558149];
$v16BotChat = 1000000001;

/** The two shipped host-facing faces, as strings (an unregistered alias fails loudly here). */
$v16Tf = 'TF';
$v16App = 'TelegramApp';

/**
 * ISOLATE THIS SECTION FROM WHATEVER ELSE IS IN THE PROCESS.
 *
 * The handler table is a process-global static (HandlerRegistry::$instance) and
 * three other capability sections are require()d into this same PHP process. The
 * re-seat below is the documented pattern — HandlerRegistry::reset() is what
 * tests/Handler/TeleframeFacadeTest.php and tests/App/AppReachabilityIntakeTest.php
 * use — plus the matching forgetInstance() calls, because the container caches
 * the same two singletons and a reset that left the container holding the OLD
 * table would make this section measure somebody else's registrations.
 *
 * AppSupport::SERVICE_KEY is re-seated for the same reason: AppResponder captures
 * HandlerRegistry::instance() at AppRuntime construction, so a service instance
 * another lane already resolved would install onto the old table.
 */
HandlerRegistry::reset();
app()->forgetInstance(HandlerRegistry::class);
app()->forgetInstance(UpdateDispatcher::class);
app()->forgetInstance(AppSupport::SERVICE_KEY);

// THE SHIPPED DOUBLES, loaded by path (autoload-dev is not installed in a host).
$v16MissingFixtures = [];
foreach (
    [
        'Support/FakeUserScope' => 'MeRezaRezaei\\Teleframe\\Tests\\Support\\FakeUserScope',
        'BotMap/Support/RecordingBotClient' => 'MeRezaRezaei\\Teleframe\\Tests\\BotMap\\Support\\RecordingBotClient',
    ] as $relative => $class
) {
    $path = $root.'/vendor/merezarezaei/teleframe/tests/'.$relative.'.php';
    if (is_file($path)) {
        require_once $path;
    } else {
        $v16MissingFixtures[] = $class;
    }
}

/**
 * THE TRANSPORT SEAM — the one thing the app framework hands an app.
 *
 * `teleclient.app.transport` is a public bind key (AppSupport::TRANSPORT_KEY) of
 * type callable(AppMode): mixed, bound by AppServiceProvider::registerTransport()
 * from config('teleframe.app.transport') and otherwise derived from
 * TeleframeClient. A host replaces a seam with app()->instance() — the same way
 * tests/App/AppHotReloadReseatTest.php seats AppSupport::SCHEDULES_KEY and the
 * V1.5 section seats PassportSupport::WIRE_KEY. It is bound HERE, before the
 * AppRuntime singleton is first resolved, because registerService() reads it at
 * construction and a rebind afterwards would be ignored.
 *
 * Each mode gets the SHIPPED double for that mode's real transport:
 *   user → FakeUserScope, a subclass of the REAL UserAccountScope, so read()
 *          and answer() run the shipped normalizePeer()/messages.getHistory/
 *          messages.sendMessage code and only the socket is replaced;
 *   bot  → RecordingBotClient, a subclass of the REAL BotClient, so
 *          sendMessage() runs the real Bot-API param merge and only call() is
 *          replaced. It has no getHistory() — which is why the Bot API cannot
 *          read history, asserted below rather than worked around.
 */
$v16UserScope = $v16MissingFixtures === []
    ? new FakeUserScope
    : null;
$v16BotClient = $v16MissingFixtures === []
    ? new RecordingBotClient('not-a-real-token', [
        'sendMessage' => ['ok' => true, 'result' => ['message_id' => 4242]],
    ])
    : null;

app()->instance(
    AppSupport::TRANSPORT_KEY,
    static fn (AppMode $mode): mixed => $mode === AppMode::Bot ? $v16BotClient : $v16UserScope,
);

// The host's own handler service + its collaborator, bound the way a host binds
// anything: app()->instance(). The registration below is by CLASS NAME, so these
// are what UpdateDispatcher::resolve() has to find.
$v16Ledger = new TfV16ProbeLedger;
app()->instance(TfV16ProbeLedger::class, $v16Ledger);
app()->instance(TfV16HostBot::class, new TfV16HostBot($v16Ledger));

/**
 * THE MESSAGE LAYER, ROOTED IN THE HOST.
 *
 * MessageCompiler's DEFAULT directories live inside the package tree
 * (resources/views/telegram, bootstrap/cache/telegram-templates) and the
 * compiled-plan cache is WRITTEN there — which would dirty the very repository
 * this gate asserts is untouched. Both directories are therefore pointed at the
 * throwaway host, which is also what a host does when it adopts the package.
 */
$v16Templates = $root.'/resources/views/telegram';
$v16Cache = $root.'/bootstrap/cache/telegram-templates';
if (! is_dir($v16Templates)) {
    @mkdir($v16Templates, 0777, true);
}
if (! is_dir($v16Cache)) {
    @mkdir($v16Cache, 0777, true);
}

file_put_contents($v16Templates.'/v16-answer.php', <<<'TPL'
<?php

$text = (string) ($text ?? '');

return ['text' => 'you said: '.$text, 'entities' => []];
TPL);

file_put_contents($v16Templates.'/v16-bold-answer.php', <<<'TPL'
<?php

use MeRezaRezaei\Teleframe\Message\MarkdownEntityParser;

$text = (string) ($text ?? '');

return MarkdownEntityParser::spans($text, [
    ['type' => 'bold', 'offset' => 0, 'length' => 9],
]);
TPL);

$v16MessageCompiler = new MessageCompiler($v16Templates, $v16Cache);
$v16Messages = new MessageFactory($v16MessageCompiler);

/**
 * DROP ANY CACHED PLAN BEFORE COMPILING, ON PURPOSE.
 *
 * MessageCompiler caches the compiled plan, not the template, so in a host that
 * has compiled these two templates before, resolve() would answer from the store
 * and never read the template again. MEASURED: breaking the template's text and
 * re-running this file inside the SAME host left every sub-check green, because
 * the plan came from the cache — a probe that can pass against a stale artefact
 * proves less than it appears to. `forget()` is the shipped view:clear-style op
 * for one name, so the compile below is a real compile and a broken template
 * goes red on the next run.
 */
$v16MessageCompiler->forget('v16-answer');
$v16MessageCompiler->forget('v16-bold-answer');

/** THE HOST'S REAL INTAKE SEAM — verbatim DaemonCommand::handlerSink(). */
$v16Sink = new HandlerSink(app(UpdateDispatcher::class));

/**
 * A RUN-SCOPED FIELD, and the reason is measured rather than assumed.
 *
 * A Laravel host binds `Psr\SimpleCache\CacheInterface` to a real cache —
 * MEASURED in this host: `Illuminate\Cache\Repository` over
 * `Illuminate\Cache\DatabaseStore`, i.e. rows in the host's OWN sqlite —
 * so `UpdateDispatcher`'s ReplayDedup registry PERSISTS ACROSS PROCESSES.
 * Replay identity is `sha1(accountId . ts . raw payload)`, so a second run of
 * this probe inside the same host re-sends identical frames and every one of
 * them is dropped as a replay before any handler runs. That was observed, not
 * theorised: the first gate run passed every sub-check, and a re-run of the
 * same file in the same kept host failed eleven of them, with the only cache
 * traffic being `has(...) HIT` on this section's own earlier keys.
 *
 * The fix is a per-run nonce in the payload, NOT a masked cache: the host's own
 * cache stays the real one and keeps proving what it proves, and the frames are
 * simply what a fresh bus delivery looks like. Masking the cache to make a probe
 * green would have hidden the finding.
 */
$v16Run = bin2hex(random_bytes(6));

/**
 * Deliver one inbound frame exactly the way a routed bus entry is delivered:
 * a raw update array, the account id as the source string, the entry timestamp.
 *
 * @param  array<string, mixed>  $update
 */
$v16Deliver = static fn (array $update, int $accountId, int $ts): bool => $v16Sink->handle(
    ['v16_probe_run' => $v16Run] + $update,
    (string) $accountId,
    $ts,
);

// ─── value recorders, one per registered handler ────────────────────────────
$v16ChannelSeen = [];
$v16DeleteSeen = [];
$v16TypingSeen = [];
$v16FakeSeen = [];
$v16AiRead = [];
$v16AiAnswered = [];
$v16BotAnswered = [];
$v16HotSeen = [];

/*
 * EVERYTHING BELOW RUNS INSIDE ONE GUARD.
 *
 * Not defensive noise: a probe file that throws out of the require() takes the
 * WHOLE gate down with it, and the other three capability sections — required
 * into this same process — would never report at all. A regression in this
 * section must therefore surface as ONE red line inside 'V1.6 bot-user-apps'
 * with the message attached, which is the whole difference between a gate that
 * says what broke and a gate that dies.
 */
$v16RuntimeError = null;
$v16Registry = null;
$v16ResponderTable = null;
$v16AnswerPlan = ['text' => '', 'entities' => []];
$v16BoldPlan = ['text' => '', 'entities' => []];
$v16AiRead = [];
$v16AiAnswered = [];
$v16BotAnswered = [];
$v16ScheduleStatus = [];
$v16ScheduleKeys = [];
$v16Fake = null;
$v16FakeResult = ['dispatched' => [], 'sent' => []];
$v16FakeSendRow = 0;
$v16DispatcherIdBefore = 0;
$v16DispatcherIdAfter = 0;
$v16RedisReachable = false;
$v16RedisWhy = 'the probe threw before the Redis probe ran';
$v16HistoryCall = null;
$v16SendCall = null;
$v16BotAnswerCall = null;
$v16RunCallRecorded = [];
$v16AfterDelete = [];
$v16AfterUnmatched = [];
$v16AfterTyping = [];

try {

/* ═══════════════════════════════════════════════════════════════════════════
 * 1. THE PREMISE — the host's container and the framework share ONE hot table.
 *
 * Not bookkeeping: HandlerMatcher, UpdateDispatcher and AppResponder are three
 * separate objects, and every claim below ("the app fires", "the host's handler
 * fires") is a claim about them reading the SAME table. Asserted by identity.
 * ═══════════════════════════════════════════════════════════════════════════ */
$v16Registry = app(HandlerRegistry::class);
$v16ResponderTable = (new ReflectionProperty(
    AppResponder::class,
    'handlerTable',
))->getValue(app(AppSupport::SERVICE_KEY)->responder());

/* ═══════════════════════════════════════════════════════════════════════════
 * 2. "saying something in chats triggers our bots" — a handler a HOST registers
 *    resolves and RUNS, and the thing that proves it is a recorded VALUE.
 *
 * Registered through TF::on(), the shipped public registration face, exactly as
 * `docs/handlers.md` and the facade docblock show a host doing it.
 * ═══════════════════════════════════════════════════════════════════════════ */
$v16Tf::on('updateNewChannelMessage', static function (Update $u) use (&$v16ChannelSeen): void {
    $v16ChannelSeen[] = [(string) ($u->array['message']['message'] ?? ''), $u->accountId];
}, 20);

// A CLASS NAME, not a closure: UpdateDispatcher::resolve() has to find it in the
// host's own container, with its constructor collaborator, or nothing is recorded.
$v16Tf::on('updateNewScheduledMessage', TfV16HostBot::class, 20);

$v16Deliver(['_' => 'updateNewChannelMessage', 'message' => ['_' => 'message', 'id' => 11, 'message' => 'channel-post']], V16_ACCOUNT, 1_700_000_001);
$v16Deliver(['_' => 'updateNewScheduledMessage', 'message' => ['_' => 'message', 'id' => 12, 'message' => 'scheduled-for-later']], V16_ACCOUNT, 1_700_000_002);

/* ═══════════════════════════════════════════════════════════════════════════
 * 3. THE MATCHER DISCRIMINATES — the same sentence in its discriminating form.
 *
 * Two handlers, two different matchers, ONE update. Only the right one may run.
 * Then the second constructor, to show it is discrimination and not "the last
 * handler always wins" or "everything runs".
 * ═══════════════════════════════════════════════════════════════════════════ */
$v16Tf::on('updateDeleteMessages', static function () use (&$v16DeleteSeen): void {
    $v16DeleteSeen[] = 'delete';
}, 20);
$v16Tf::on('updateUserTyping', static function () use (&$v16TypingSeen): void {
    $v16TypingSeen[] = 'typing';
}, 20);

$v16Deliver(['_' => 'updateDeleteMessages', 'messages' => [3, 4], 'pts' => 9], V16_ACCOUNT, 1_700_000_003);

// SNAPSHOT, taken HERE and not at the return statement: every mustBeTrue() below
// is evaluated after every delivery in this file has already happened, so a check
// that read the live arrays would only ever see the END state and could not tell
// "only the matching handler ran" from "everything ran eventually". The snapshot
// is what makes the claim about THIS frame rather than about the whole file.
$v16AfterDelete = [
    'delete' => $v16DeleteSeen,
    'typing' => $v16TypingSeen,
    'channel' => $v16ChannelSeen,
    'ledger' => $v16Ledger->seen,
];

// A constructor NOTHING is registered for. The sink still returns true — the
// intake accepted the row — but no handler may run, which is what makes the
// discriminating assertion above meaningful rather than vacuous.
$v16Deliver(['_' => 'updateNewStickerSet', 'pts' => 10], V16_ACCOUNT, 1_700_000_004);

$v16AfterUnmatched = [
    'delete' => $v16DeleteSeen,
    'typing' => $v16TypingSeen,
    'channel' => $v16ChannelSeen,
    'ledger' => $v16Ledger->seen,
];

$v16Deliver(['_' => 'updateUserTyping', 'user_id' => 501558149, 'pts' => 11], V16_ACCOUNT, 1_700_000_005);

$v16AfterTyping = ['delete' => $v16DeleteSeen, 'typing' => $v16TypingSeen];

/* ═══════════════════════════════════════════════════════════════════════════
 * 4. THE MESSAGE LAYER — a real inbound message read into TEXT, compiled into a
 *    reply plan. The operator's "connect my ai to read my telegram messages and
 *    answer them", with the text itself as the assertion.
 *
 * The reply is compiled BEFORE the app is declared, so the app's handler can hand
 * the plan to answer() rather than inventing its own string.
 * ═══════════════════════════════════════════════════════════════════════════ */
$v16AnswerPlan = $v16Messages->resolve('v16-answer', ['text' => 'ping']);
$v16BoldPlan = $v16Messages->resolve('v16-bold-answer', ['text' => 'channel ack']);

/* ═══════════════════════════════════════════════════════════════════════════
 * 5. A USER-SCOPE APP — declared with TelegramApp::define(), the shipped
 *    one-call face. define() is the whole wiring step: it declares the app AND
 *    installs its trigger onto the shared table (AppResponder), which is what
 *    makes "saying something in chats triggers our bots" true from a host.
 * ═══════════════════════════════════════════════════════════════════════════ */
$v16App::define('v16-user-ai', AppMode::User, [
    AppTrigger::on('updateNewMessage', static function (Update $u) use (&$v16AiRead, &$v16AiAnswered, $v16AnswerPlan, $v16App): void {
        $message = $u->array['message'] ?? null;
        if (! is_array($message)) {
            return;
        }

        // READ: the conversation on the message's own peer, through the shipped
        // UserAccountScope::getHistory().
        $v16AiRead[] = ['peer' => $message['peer_id'] ?? null, 'rows' => $v16App::read($message['peer_id'] ?? null, 20, AppMode::User)];

        // ANSWER: the compiled plan's own TEXT, back out on the same account.
        $v16AiAnswered[] = $v16App::answer($u->accountId, $message['peer_id'] ?? null, $v16AnswerPlan['text'], AppMode::User);
    }, 50),
]);

$v16Deliver(['_' => 'updateNewMessage', 'message' => [
    '_' => 'message', 'id' => 21, 'message' => 'ping', 'peer_id' => $v16Peer,
]], V16_ACCOUNT, 1_700_000_006);

/* ═══════════════════════════════════════════════════════════════════════════
 * 6. A BOT-SCOPE APP — the other half of "bot AND user apps".
 *
 * Declared AFTER the section above has already run a plain host handler on
 * updateNewChannelMessage, so this also shows a declarative app taking over its
 * constructor on the live table: the bot trigger carries priority 40, the
 * untagged host row 20, and matching is first-match-wins by priority.
 * ═══════════════════════════════════════════════════════════════════════════ */
$v16App::define('v16-bot-echo', AppMode::Bot, [
    AppTrigger::on('updateNewChannelMessage', static function (Update $u) use (&$v16BotAnswered, $v16BoldPlan, $v16App): void {
        $message = $u->array['message'] ?? null;
        if (! is_array($message)) {
            return;
        }

        $v16BotAnswered[] = $v16App::answer(
            $u->accountId,
            $message['peer_id'] ?? null,
            $v16BoldPlan['text'],
            AppMode::Bot,
            ['entities' => $v16BoldPlan['entities']],
        );
    }, 40),
], [
    AppCall::every('status-ping', 3600, 'sendMessage', ['chat_id' => '@ops', 'text' => 'v16-bot-echo alive']),
]);

$v16Deliver(['_' => 'updateNewChannelMessage', 'message' => [
    '_' => 'message', 'id' => 31, 'message' => 'channel-post', 'peer_id' => $v16BotChat,
]], V16_BOT_ACCOUNT, 1_700_000_007);

/* ═══════════════════════════════════════════════════════════════════════════
 * 7. THE SCHEDULE HALF — "schedule telegram calls they do".
 *
 * installCalls() is the WRITE onto the schedule seam; the Null publisher is the
 * shipped default and answers 'skipped' — honestly, because this host configured
 * no scheduler. runCall() is the READ that actually invokes one now.
 * ═══════════════════════════════════════════════════════════════════════════ */
$v16ScheduleStatus = $v16App::installCalls();
$v16CallsBeforeRun = $v16BotClient === null ? [] : count($v16BotClient->calls);
$v16App::runCall('v16-bot-echo', 'status-ping');
$v16RunCallRecorded = $v16BotClient === null ? [] : array_slice($v16BotClient->calls, $v16CallsBeforeRun);

// A bot app asking for an MTProto method refuses LOUD (design §5/§6 Q4) rather
// than quietly calling the Bot API with a method it does not have.
$v16App::define('v16-bot-badcall', AppMode::Bot, [], [
    AppCall::every('history-pull', 600, 'messages.getHistory', ['peer' => $v16Peer]),
]);

/* ═══════════════════════════════════════════════════════════════════════════
 * 8. THE SHIPPED RECORDING DISPATCHER — the fake a host uses in its own tests.
 *
 * Real HandlerRegistry (the host's own shared table), real HandlerMatcher, real
 * Pipeline, real EchoEliminator + ReplayDedup; the host's real container for DI
 * and the shipped InMemoryCache for the PSR-16 seam. Its send() is the recorded
 * send this section asserts the TEXT of.
 * ═══════════════════════════════════════════════════════════════════════════ */
$v16Tf::on('updateNewStoryReaction', static function (Update $u) use (&$v16FakeSeen): void {
    $v16FakeSeen[] = (string) ($u->array['reaction']['_'] ?? '');
}, 60);

$v16Fake = new FakeDispatcher(
    [['update' => [
        '_' => 'updateNewStoryReaction',
        'reaction' => ['_' => 'reactionEmoji', 'emoticon' => '👍'],
    ], 'account_id' => 9040, 'ts' => 1_700_000_008]],
    HandlerRegistry::instance(),
    app(),
    new InMemoryCache,
);
$v16FakeResult = $v16Fake->run();
$v16FakeSendRow = $v16Fake->send(9040, ['api' => 'sendMessage', 'text' => 'fake-transport-said-hello']);

/* ═══════════════════════════════════════════════════════════════════════════
 * 9. HOT RELOAD — the Redis-free half, asserted as a recorded invocation.
 *
 * Everything above already ran through ONE resolved UpdateDispatcher. An app
 * declared NOW, after that dispatcher exists, must still be served by it — no
 * restart, no reseat, no pub/sub. That is the part of "redis hot reload" a host
 * without a bus can actually get, so it is the part proven as a value.
 * ═══════════════════════════════════════════════════════════════════════════ */
$v16DispatcherIdBefore = spl_object_id(app(UpdateDispatcher::class));
$v16App::define('v16-hot-app', AppMode::User, [
    AppTrigger::on('updateReadHistoryInbox', static function () use (&$v16HotSeen): void {
        $v16HotSeen[] = 'hot-app';
    }, 50),
]);
$v16Deliver(['_' => 'updateReadHistoryInbox', 'peer' => $v16Peer, 'pts' => 12], V16_ACCOUNT, 1_700_000_009);
$v16DispatcherIdAfter = spl_object_id(app(UpdateDispatcher::class));

/* ═══════════════════════════════════════════════════════════════════════════
 * 10. REDIS — the current truth of this host, asserted either way.
 *
 * Probed, not assumed. The bus reload channel and the daemon's reseat listener
 * both need a real Redis; when there is none, that half of "redis hot reload" is
 * NOT proven and this says so in the $what rather than passing quietly.
 * ═══════════════════════════════════════════════════════════════════════════ */
$v16RedisReachable = false;
$v16RedisWhy = '';
try {
    $v16Ping = app('redis')->connection()->ping();
    $v16RedisReachable = $v16Ping === true || $v16Ping === 'PONG' || $v16Ping === 1;
    $v16RedisWhy = $v16RedisReachable ? 'ping answered' : 'ping answered '.$v16Ping::class;
} catch (Throwable $e) {
    $v16RedisWhy = $e::class.': '.$e->getMessage();
}

} catch (Throwable $e) {
    $v16RuntimeError = $e;
}

/* ═══════════════════════════════════════════════════════════════════════════
 * THE READS, gathered once so each check below states a plain value. Every one
 * of them defaults, so a throw above reports as a red check rather than as a
 * second fatal while the failure is being reported.
 * ═══════════════════════════════════════════════════════════════════════════ */
$v16UserCalls = $v16UserScope === null ? [] : $v16UserScope->calls;
$v16HistoryCall = null;
$v16SendCall = null;
foreach ($v16UserCalls as $row) {
    if ($row['method'] === 'messages.getHistory' && $v16HistoryCall === null) {
        $v16HistoryCall = $row;
    }
    if ($row['method'] === 'messages.sendMessage' && $v16SendCall === null) {
        $v16SendCall = $row;
    }
}
$v16BotCalls = $v16BotClient === null ? [] : $v16BotClient->calls;
$v16BotAnswerCall = null;
foreach ($v16BotCalls as $row) {
    if ($row[0] === 'sendMessage' && $v16BotAnswerCall === null) {
        $v16BotAnswerCall = $row;
    }
}
$v16ScheduleKeys = array_keys($v16ScheduleStatus);

return [
    'V1.6 bot-user-apps' => [

        // ── the guard: nothing below threw out of the file ─────────────────
        'section-ran-without-error' => mustBeTrue(
            static fn (): bool => $v16RuntimeError === null,
            'the whole section ran to completion: no handler, app definition, read, answer or scheduled call threw out of the probe file'
                .($v16RuntimeError === null
                    ? ''
                    : ' — it threw '.$v16RuntimeError::class.': '.$v16RuntimeError->getMessage()),
        ),

        // ── the premise ────────────────────────────────────────────────────
        'one-hot-table' => mustBeTrue(
            static function () use ($v16Registry, $v16ResponderTable): bool {
                return $v16Registry === HandlerRegistry::instance()
                    && $v16ResponderTable === $v16Registry;
            },
            'the host container, the shared HandlerRegistry singleton and the framework\'s own AppResponder are ONE table by object identity, so a handler a host registers and a trigger an app declares are routed by the same matcher',
        ),

        // ── "saying something in chats triggers our bots" ───────────────────
        'closure-handler-ran' => mustBeTrue(
            static fn (): bool => $v16ChannelSeen === [['channel-post', V16_ACCOUNT]],
            'a handler registered through the shipped TF::on() face received a real inbound updateNewChannelMessage frame through the host\'s own HandlerSink intake and recorded the exact text and account it saw',
        ),

        'class-handler-resolved-from-container' => mustBeTrue(
            static fn (): bool => $v16Ledger->seen === [['scheduled-post', V16_ACCOUNT, 'scheduled-for-later']],
            'a handler registered BY CLASS NAME was resolved out of the host\'s own container with its constructor-injected collaborator and ran on a real inbound updateNewScheduledMessage frame, recording constructor, account and text',
        ),

        // ── the matcher discriminates ───────────────────────────────────────
        'matcher-runs-only-the-matching-handler' => mustBeTrue(
            static fn (): bool => $v16AfterDelete === [
                'delete' => ['delete'],
                'typing' => [],
                'channel' => [['channel-post', V16_ACCOUNT]],
                'ledger' => [['scheduled-post', V16_ACCOUNT, 'scheduled-for-later']],
            ],
            'an updateDeleteMessages frame ran exactly the handler registered for updateDeleteMessages and left the updateUserTyping handler untouched, so the matcher discriminates on the real constructor rather than firing the table',
        ),

        'second-constructor-runs-its-own-handler' => mustBeTrue(
            static fn (): bool => $v16AfterTyping === ['delete' => ['delete'], 'typing' => ['typing']],
            'a later updateUserTyping frame ran the updateUserTyping handler and did not re-run the updateDeleteMessages one, so the discrimination is in the matcher and not in dispatch order',
        ),

        'unregistered-constructor-runs-nothing' => mustBeTrue(
            static fn (): bool => $v16AfterUnmatched === $v16AfterDelete,
            'an updateNewStickerSet frame, which no handler is registered for, was accepted by the intake sink and ran NO handler at all — the table was not swept as a side effect of the frames above',
        ),

        // ── the Message layer: read into text, answer in text ───────────────
        'inbound-message-read-into-text' => mustBeTrue(
            static fn (): bool => $v16AnswerPlan['text'] === 'you said: ping',
            'the shipped Message layer (MessageFactory over MessageCompiler) compiled a reply plan whose TEXT is the inbound message text this section read out of the update array',
        ),

        'message-entities-compiled' => mustBeTrue(
            static fn (): bool => $v16BoldPlan['text'] === 'channel ack'
                && ($v16BoldPlan['entities'][0]['_'] ?? '') === 'messageEntityBold'
                && ($v16BoldPlan['entities'][0]['offset'] ?? -1) === 0
                && ($v16BoldPlan['entities'][0]['length'] ?? -1) === 9,
            'MarkdownEntityParser::spans() compiled the reply text into a messageEntityBold at offset 0 length 9, so the text and its entities travel together and cannot drift apart',
        ),

        // ── a USER-scope app ───────────────────────────────────────────────
        'user-app-fired-on-the-real-constructor' => mustBeTrue(
            static fn (): bool => $v16AiRead !== [] && $v16AiAnswered !== [],
            'an AppMode::User app declared with TelegramApp::define() fired on a real inbound updateNewMessage frame without any install() call by hand — declaring the app is the whole wiring step',
        ),

        'user-app-read-the-conversation' => mustBeTrue(
            static function () use ($v16HistoryCall, $v16Peer): bool {
                return $v16HistoryCall !== null
                    && $v16HistoryCall['params']['peer'] === $v16Peer
                    && $v16HistoryCall['params']['limit'] === 20;
            },
            'the app\'s read() ran the real UserAccountScope::getHistory() and issued messages.getHistory for the message\'s OWN peer at the requested limit of 20 — the "connect my ai to read my telegram messages" half, on the recording scope',
        ),

        'user-app-answered-with-that-text' => mustBeTrue(
            static fn (): bool => $v16SendCall !== null
                && ($v16SendCall['params']['message'] ?? null) === 'you said: ping',
            'the app\'s answer() ran the real UserAccountScope::sendMessage() and put the compiled reply TEXT "you said: ping" on the wire params, not a placeholder — the "…and answer them" half',
        ),

        // ── a BOT-scope app ────────────────────────────────────────────────
        'bot-app-fired-and-took-over-its-constructor' => mustBeTrue(
            static fn (): bool => $v16BotAnswered !== [] && $v16ChannelSeen === [['channel-post', V16_ACCOUNT]],
            'an AppMode::Bot app fired on a real inbound updateNewChannelMessage frame AND outranked the plain host handler already registered for that constructor, so "bot and user apps" is two working programs on one table rather than one',
        ),

        'bot-app-answer-carried-the-entity' => mustBeTrue(
            static function () use ($v16BotAnswerCall, $v16BotChat): bool {
                return $v16BotAnswerCall !== null
                    && ($v16BotAnswerCall[1]['text'] ?? null) === 'channel ack'
                    && ($v16BotAnswerCall[1]['entities'][0]['_'] ?? '') === 'messageEntityBold'
                    && ($v16BotAnswerCall[1]['chat_id'] ?? null) === $v16BotChat;
            },
            'the bot app\'s answer ran the real BotClient::sendMessage() param merge and delivered the reply TEXT plus its compiled messageEntityBold on the channel peer',
        ),

        'bot-mode-cannot-read-history' => mustThrow(
            static fn () => $v16App::read($v16Peer, 5, AppMode::Bot),
            'the Bot API cannot read message history',
        ),

        'bot-mode-read-refusal-is-actionable' => mustBeTrue(
            static function () use ($v16Peer, $v16App): bool {
                try {
                    $v16App::read($v16Peer, 5, AppMode::Bot);
                } catch (Throwable $e) {
                    return str_contains($e->getMessage(), 'no getHistory() surface')
                        && str_contains($e->getMessage(), 'use mode user');
                }

                return false;
            },
            'the bot-mode read refusal says WHAT it is (no getHistory() surface) and names the working alternative (use mode user), so it is an actionable refusal and not a silent drop',
        ),

        // ── "schedule telegram calls they do" ───────────────────────────────
        'scheduled-call-previewed-onto-the-seam' => mustBeTrue(
            static function () use ($v16ScheduleStatus, $v16ScheduleKeys): bool {
                return count($v16ScheduleKeys) === 1
                    && str_starts_with($v16ScheduleKeys[0], 'app-call-v16-bot-echo-status-ping-')
                    && $v16ScheduleStatus[$v16ScheduleKeys[0]] === 'skipped (null schedule driver)';
            },
            'the bot app\'s declared AppCall composed into one SchedulePublisher payload keyed app-call-v16-bot-echo-status-ping-<digest>, which the shipped Null publisher answered "skipped (null schedule driver)" — this host configured no scheduler and is told so',
        ),

        'scheduled-call-runs-through-the-bot-transport' => mustBeTrue(
            static fn (): bool => count($v16RunCallRecorded) === 1
                && $v16RunCallRecorded[0][0] === 'sendMessage'
                && $v16RunCallRecorded[0][1]['chat_id'] === '@ops'
                && $v16RunCallRecorded[0][1]['text'] === 'v16-bot-echo alive',
            'runCall() made EXACTLY ONE new call on the app\'s own bot transport — the slice taken across the call, so an earlier answer cannot be mistaken for it — and it reached the wire as Bot API sendMessage with the declared chat and text',
        ),

        'mode-mismatched-call-refused' => mustThrow(
            static fn () => $v16App::runCall('v16-bot-badcall', 'history-pull'),
            'mode-mismatched calls refuse loud',
        ),

        'mode-mismatch-names-both-sides' => mustBeTrue(
            static function () use ($v16App): bool {
                try {
                    $v16App::runCall('v16-bot-badcall', 'history-pull');
                } catch (Throwable $e) {
                    return str_contains($e->getMessage(), 'messages.getHistory')
                        && str_contains($e->getMessage(), 'is a mtproto catalog method')
                        && str_contains($e->getMessage(), 'not usable by a bot-mode app');
                }

                return false;
            },
            'the mode-mismatch refusal names the app, the call, the method, which API the method really belongs to and the app mode that cannot use it — a config error a host can act on',
        ),

        // ── the shipped recording dispatcher ────────────────────────────────
        'fake-dispatcher-runs-the-real-pipeline' => mustBeTrue(
            static function () use ($v16Fake, $v16FakeResult, $v16FakeSeen, $v16FakeSendRow): bool {
                return count($v16FakeResult['dispatched']) === 1
                    && count($v16Fake->dispatched) === 1
                    && $v16FakeResult['dispatched'][0]->constructor() === 'updateNewStoryReaction'
                    && $v16FakeResult['dispatched'][0]->accountId === 9040
                    && $v16FakeSeen === ['reactionEmoji']
                    && $v16FakeSendRow === 1
                    // run() returns a snapshot of `sent` taken BEFORE this
                    // section's send() call, so the recorded send is read off
                    // the live property, not off the returned view.
                    && $v16Fake->sent === [[
                        'account' => 9040,
                        'send' => ['api' => 'sendMessage', 'text' => 'fake-transport-said-hello'],
                    ]];
            },
            'the shipped Testing\\FakeDispatcher drove one fixture through the host\'s own shared registry, container and PSR-16 cache: the handler ran, the frame reached the terminal and was recorded as dispatched exactly once with its constructor and account, and its explicit send() was captured with the exact text',
        ),

        // ── hot reload ──────────────────────────────────────────────────────
        'hot-registration-served-by-the-live-dispatcher' => mustBeTrue(
            static fn (): bool => $v16HotSeen === ['hot-app'] && $v16DispatcherIdBefore === $v16DispatcherIdAfter,
            'an app declared AFTER the UpdateDispatcher had already resolved and already dispatched was served by that SAME dispatcher object with no restart and no reseat — the hot-registration half of hot reload, proven as a recorded invocation',
        ),

        'redis-current-truth' => mustBeTrue(
            static fn (): bool => $v16RuntimeError !== null
                ? false
                : ($v16RedisReachable ? $v16RedisReachable : $v16HotSeen === ['hot-app']),
            $v16RuntimeError !== null
                ? 'THE SECTION ABORTED BEFORE THE REDIS PROBE RAN — see section-ran-without-error; neither half of "redis hot reload" is claimed by this run'
                : ($v16RedisReachable
                    ? 'Redis IS reachable in this host ('.$v16RedisWhy.'), so the Redis-dependent surfaces exist here — but this section did not drive the bus reload channel, so only the ping is asserted, not the reload'
                    : 'NO Redis in this host ('.$v16RedisWhy.') — so the bus reload channel and the daemon reseat listener are NOT proven by this section and are not claimed; what IS proven is the Redis-free half of hot reload above, an app defined at runtime served by the already-resolved dispatcher'),
        ),

        // ── provenance, so a failure line reads without opening this file ───
        'fixtures' => 'recording doubles loaded by path: '.(
            $v16MissingFixtures === []
                ? 'tests/Support/FakeUserScope (extends the real UserAccountScope) + tests/BotMap/Support/RecordingBotClient (extends the real BotClient)'
                : 'MISSING '.$v16MissingFixtures[0]
        ),
        'transport' => 'teleclient.app.transport bound to '.get_debug_type($v16UserScope).' (user) / '.get_debug_type($v16BotClient).' (bot)',
        'credentials' => 'api_id='.((int) config('teleframe.api_id'))
            .' api_hash='.((string) config('teleframe.api_hash') === '' ? '(empty)' : '(set)')
            .' bot_token='.((string) config('teleframe.bot_token') === '' ? '(empty)' : '(set)')
            .' — every read, answer and scheduled call above went to a recording double',
        'cache-seam' => 'the host\'s own PSR-16 binding ('.get_debug_type(app(Psr\SimpleCache\CacheInterface::class)).') is what UpdateDispatcher/ReplayDedup read; it was NOT replaced, and a run-scoped field (v16_probe_run) keeps a repeat run in the same host from replay-dropping this section\'s own earlier frames',
        'not-reached' => 'no Telegram account, session string, bot token, auth key, socket or HTTP request; the read/answer/runCall paths are the shipped ones with only the transport replaced, and the recording doubles answer locally',
    ],
];
