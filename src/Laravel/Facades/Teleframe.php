<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\Bot\Services\BotClient;
use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\Passport\PassportDecryptor;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Core\Types\InlineKeyboard;
use MeRezaRezaei\Teleframe\Core\Types\InputMedia;
use MeRezaRezaei\Teleframe\Core\Types\InputPeer;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\DashboardRoutes;
use MeRezaRezaei\Teleframe\Laravel\Media\StorageMedia;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;

/**
 * Main Teleframe Facade for Laravel.
 *
 * @method static UserAccountScope user(?int $accountId = null, string|SessionData|null $session = null, int $dcId = 2, ?int $apiId = null, ?string $apiHash = null, ?array $proxyConfig = null) Connect as a User MTProto account.
 * @method static UserAccountScope fromSession(string $sessionString, ?int $apiId = null, ?string $apiHash = null, ?array $proxyConfig = null) Initialize a User MTProto account directly from an exported base64 session string.
 * @method static UserAccountScope forAccount(?int $accountId = null, string|SessionData|null $session = null, int $dcId = 2, ?int $apiId = null, ?string $apiHash = null, ?array $proxyConfig = null) Alias for user().
 * @method static BotClient bot(?string $botToken = null, ?array $proxyConfig = null) Connect as a Telegram Bot over HTTP Bot API.
 * @method static \MeRezaRezaei\Teleframe\Bot\Services\BotAccountScope botMtproto(?string $botToken = null, string|SessionData|null $session = null, int $dcId = 2, ?int $apiId = null, ?string $apiHash = null, ?array $proxyConfig = null) Connect as a Telegram Bot directly over high-speed binary MTProto 2.0.
 * @method static UserAccountScope userFromVault(?string $label = null) Named user scope from the DB-encrypted vault.
 * @method static BotClient|\MeRezaRezaei\Teleframe\Bot\Services\BotAccountScope botFromVault(?string $label = null, string $transport = 'http') Named bot client from the DB-encrypted vault ('http' or 'mtproto').
 * @method static int schemaLayer() Declared Telegram schema layer of the packaged artifacts.
 * @method static AppRuntime app() The bot/user app framework — declare apps, read messages, answer them. See Facades\TelegramApp for the full declarative surface.
 * @method static self onMessage(string|array|\Closure $handler, int $priority = 0) The `*` catch-all subscription on the shared handler table.
 * @method static self on(string $match, string|array|\Closure $handler, int $priority = 0, bool $onOwn = false) An exact / prefix / `%s` match subscription.
 * @method static int send(int $accountId, array $send) Record a reply's ids in the shared echo-elimination registry.
 *
 * @see TeleframeClient
 * @see UserAccountScope
 * @see BotClient
 * @see InputPeer
 * @see InlineKeyboard
 * @see InputMedia
 * @see EntityParser
 * @see StorageMedia
 * @see PassportDecryptor
 */
class Teleframe extends Facade
{
    /**
     * Register the Horizon-style dashboard routes (index page + JSON API)
     * under config('teleframe.dashboard.prefix') with the configured guard.
     * Call once from your app's provider boot():
     *
     *     Teleframe::routes();
     *
     * Token-based hosts: Teleframe::routes(prefix: 'api', middleware: ['auth:sanctum']).
     *
     * @param  list<string>|null  $middleware
     */
    public static function routes(?string $prefix = null, ?array $middleware = null): void
    {
        DashboardRoutes::register($prefix, $middleware);
    }

    /**
     * The bot/user app framework through the documented alias (reachability
     * fix 2026-09-26, BREAK 3).
     *
     * This accessor is TeleframeClient, and TeleframeClient has no app() — so
     * `Teleframe::app()`, the call the design docs, the plan and the
     * coverage report all advertise, raised "Call to undefined method
     * TeleframeClient::app()". The compose-not-own face that DOES have app()
     * (MeRezaRezaei\Teleframe\Teleframe, bound as a container singleton)
     * was unreachable through the alias, so the documented one-liner for the
     * whole app framework was dead.
     *
     * These four statics forward to that composed face. They are explicit
     * rather than __call magic: the client is the wire surface and the
     * compose face is the module surface, and an alias that silently mixed
     * them is how this broke in the first place. Everything else keeps
     * resolving against TeleframeClient exactly as before.
     */
    public static function app(): AppRuntime
    {
        return self::compose()->app();
    }

    /** The `*` catch-all subscription on the shared handler table. */
    public static function onMessage(string|array|\Closure $handler, int $priority = 0): self
    {
        self::compose()->onMessage($handler, $priority);

        return new self;
    }

    /** An exact / prefix / `%s` match subscription on the shared table. */
    public static function on(string $match, string|array|\Closure $handler, int $priority = 0, bool $onOwn = false): self
    {
        self::compose()->on($match, $handler, $priority, $onOwn);

        return new self;
    }

    /**
     * The Q2d send path: record a reply's ids in the shared PSR-16
     * elimination registry so its echo never re-enters handlers.
     *
     * @param  array<string, mixed>  $send
     */
    public static function send(int $accountId, array $send): int
    {
        return self::compose()->send($accountId, $send);
    }

    /**
     * The one-class compose-not-own face bound in the container. A host
     * that booted no provider gets Laravel's own loud
     * BindingResolutionException naming the class and the providers it
     * looked for — a facade that silently no-ops is how a documented call
     * turns into a mystery.
     */
    private static function compose(): \MeRezaRezaei\Teleframe\Teleframe
    {
        return app()->make(\MeRezaRezaei\Teleframe\Teleframe::class);
    }

    protected static function getFacadeAccessor(): string
    {
        return TeleframeClient::class;
    }
}
