<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppRegistry;
use MeRezaRezaei\Teleframe\App\AppResponder;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\App\AppSupport;
use MeRezaRezaei\Teleframe\App\AppTrigger;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;

/**
 * TelegramApp facade (design 2026-09-24 §3, reachability fix 2026-09-26):
 * THE code face of the bot/user app framework — "just give me a facade in
 * codes to call for what i want from it". It reads messages, answers them,
 * and registers declarative apps (name, mode bot|user, chat triggers,
 * scheduled telegram calls) onto the one shared handler pipeline.
 *
 * EVERY EXAMPLE BELOW IS EXECUTABLE AND IS CHECKED MECHANICALLY
 * (TelegramAppFacadeContractTest resolves every `@method` line in this
 * docblock, and every static call site in it, against AppRuntime). The
 * previous version of this docblock was fatal in three places — it called a
 * `service()` method AppRuntime does not have (and a Laravel Facade has no
 * __call of its own), and it fed `list()`'s view rows into register(), whose
 * parameter is an AppDefinition. A facade whose own examples fatal is worse
 * than no facade.
 *
 * The owner's sentence, end to end — an app that reads a chat and answers
 * it. See examples/ai-read-and-reply.php for the runnable version.
 *
 *     TelegramApp::define('assistant', AppMode::User, [
 *         AppTrigger::on('updateNewMessage', function (Update $u): void {
 *             $text = (string) ($u->array['message']['message'] ?? '');
 *             if ($text === '') {
 *                 return;
 *             }
 *             // read the conversation…
 *             $recent = TelegramApp::read($u->accountId, 20);
 *             // …and answer it through the same account.
 *             TelegramApp::answer($u->accountId, $u->array['message']['peer_id'], 'you said: '.$text);
 *         }),
 *     ], [
 *         AppCall::every('status-ping', 3600, 'messages.sendMessage', ['peer' => '@ops', 'message' => 'alive']),
 *     ]);
 *
 *     TelegramApp::list();                             // registry rows (a VIEW)
 *     TelegramApp::calls();                            // installSpec preview: app-call-assistant-status-ping-ed8d7e00665c
 *     TelegramApp::installCalls();                     // hand it to the schedule seam
 *     TelegramApp::runCall('assistant', 'status-ping'); // invoke one call now (offline probe)
 *     TelegramApp::transport();                        // the raw resolved scope
 *
 * The triggers are installed by AppServiceProvider::boot() — in EVERY
 * process that consumes updates, daemon included. You do not call install()
 * yourself; declaring the app is the whole wiring step.
 *
 * Class name safety: this short name is NOT the Vault model
 * `Vault\TelegramApp` (API-app credentials, src/Teleframe/Vault/) — the
 * facade lives in Laravel\Facades, a distinct FQCN and noun (program vs
 * credentials).
 *
 * Unconfigured (no teleframe.app.apps, null schedules): resolves and is
 * callable — the transport throws loud on first use, never silent-drop.
 *
 * @method static string define(string $name, AppMode $mode, array $triggers = [], array $calls = [])
 * @method static string register(AppDefinition $app) // define + install triggers in one call
 * @method static array list() // registry rows (name/mode/counts) — a view, not a definition
 * @method static array apps() // the same rows under the runtime's own name
 * @method static array calls() // installSpec preview for all registered apps
 * @method static array installCalls() // install that preview on the SchedulePublisher seam
 * @method static mixed runCall(string $app, string $call) // offline/on-demand scheduled-call probe
 * @method static mixed transport(?AppMode $mode = null) // the resolved scope (default user)
 * @method static array read(int|string|array $peer, int $limit = 50, ?AppMode $mode = null, int $offsetId = 0) // read messages
 * @method static array answer(int $accountId, int|string|array $peer, string $text, ?AppMode $mode = null, array $options = []) // send a reply
 * @method static AppDefinition resolve(string $name) // registry lookup (BotMap::for() posture)
 * @method static AppRegistry registry() // the name => AppDefinition table
 * @method static AppResponder responder() // the app -> pipeline registration bridge
 * @method static SchedulePublisher schedules() // the schedule seam
 *
 * @see AppSupport
 * @see AppRuntime
 */
class TelegramApp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AppSupport::SERVICE_KEY;
    }
}
