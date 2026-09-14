<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
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

    protected static function getFacadeAccessor(): string
    {
        return TeleframeClient::class;
    }
}
