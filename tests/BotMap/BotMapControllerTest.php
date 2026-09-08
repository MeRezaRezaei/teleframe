<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\BotMap;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use MeRezaRezaei\Teleframe\BotMap\BotMap;
use MeRezaRezaei\Teleframe\BotMap\Http\BotMapController;
use MeRezaRezaei\Teleframe\Tests\BotMap\Support\RecordingBotClient;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BotMapControllerTest extends TestCase
{
    public function test_valid_signature_invokes_the_bot_and_returns_json(): void
    {
        $clients = [];
        $map = $this->map(['sendMessage' => self::ok(['message_id' => 5])], $clients);
        $map->register('support', ['token' => '123:ABC']);
        $controller = new BotMapController($map, 'sekret');
        $body = '{"chat_id":1,"text":"hi"}';

        $response = $controller(
            $this->request('/telegram/bot-map/support/sendMessage', 'support', 'sendMessage', $body, 'sekret'),
            'support',
            'sendMessage',
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['ok' => true, 'result' => ['message_id' => 5]], json_decode($response->getContent(), true));
        self::assertSame(['sendMessage', ['chat_id' => 1, 'text' => 'hi']], $clients[1]->lastCall());
    }

    public function test_empty_body_is_a_valid_payload(): void
    {
        $clients = [];
        $map = $this->map([], $clients);
        $map->register('support', ['token' => '123:ABC']);
        $controller = new BotMapController($map, 'sekret');

        $response = $controller(
            $this->request('/telegram/bot-map/support/getMe', 'support', 'getMe', '', 'sekret'),
            'support',
            'getMe',
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['getMe', []], $clients[1]->lastCall());
    }

    public function test_missing_secret_answers_500(): void
    {
        $clients = [];
        $map = $this->map([], $clients);
        $map->register('support', ['token' => '123:ABC']);
        $controller = new BotMapController($map, null);

        $response = $controller(
            $this->request('/telegram/bot-map/support/getMe', 'support', 'getMe', '', 'sekret'),
            'support',
            'getMe',
        );

        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('secret is not configured', $response->getContent());
        self::assertCount(1, $clients, 'only the register-time probe client may exist before auth passes');
        self::assertSame(['getMyCommands', []], $clients[0]->lastCall());
    }

    public function test_invalid_signature_answers_401(): void
    {
        $clients = [];
        $map = $this->map([], $clients);
        $map->register('support', ['token' => '123:ABC']);
        $controller = new BotMapController($map, 'sekret');

        $response = $controller(
            $this->request('/telegram/bot-map/support/getMe', 'support', 'getMe', '', 'wrong'),
            'support',
            'getMe',
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertStringContainsString('Invalid BotMap signature', $response->getContent());
        self::assertCount(1, $clients, 'only the register-time probe client may exist before auth passes');
        self::assertSame(['getMyCommands', []], $clients[0]->lastCall());
    }

    public function test_signature_is_bound_to_the_bot_route_param(): void
    {
        $clients = [];
        $map = $this->map([], $clients);
        $map->register('support', ['token' => '123:ABC']);
        $controller = new BotMapController($map, 'sekret');
        $body = '';

        $response = $controller(
            $this->request('/telegram/bot-map/support/getMe', 'support', 'getMe', $body, 'sekret'),
            'other',
            'getMe',
        );

        self::assertSame(401, $response->getStatusCode(), 'signature signed for support must not replay on other');
        self::assertCount(1, $clients, 'only the register-time probe client may exist before auth passes');
        self::assertSame(['getMyCommands', []], $clients[0]->lastCall());
    }

    public function test_signature_is_bound_to_the_method_route_param(): void
    {
        $clients = [];
        $map = $this->map([], $clients);
        $map->register('support', ['token' => '123:ABC']);
        $controller = new BotMapController($map, 'sekret');
        $body = '';

        $response = $controller(
            $this->request('/telegram/bot-map/support/getMe', 'support', 'getMe', $body, 'sekret'),
            'support',
            'deleteWebhook',
        );

        self::assertSame(401, $response->getStatusCode(), 'signature signed for getMe must not replay on deleteWebhook');
        self::assertCount(1, $clients, 'only the register-time probe client may exist before auth passes');
        self::assertSame(['getMyCommands', []], $clients[0]->lastCall());
    }

    public function test_unknown_bot_answers_404(): void
    {
        $clients = [];
        $map = $this->map([], $clients);
        $map->register('support', ['token' => '123:ABC']);
        $controller = new BotMapController($map, 'sekret');

        $response = $controller(
            $this->request('/telegram/bot-map/ghost/getMe', 'ghost', 'getMe', '', 'sekret'),
            'ghost',
            'getMe',
        );

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('Unknown bot [ghost]', $response->getContent());
    }

    public function test_transport_failure_answers_502(): void
    {
        $map = new BotMap(null, null, static function (string $token): RecordingBotClient {
            return new class($token) extends RecordingBotClient {
                public function call(string $method, array $params = []): array
                {
                    throw new RuntimeException('telegram exploded');
                }
            };
        });
        $map->register('support', ['token' => '123:ABC']);
        $controller = new BotMapController($map, 'sekret');

        $response = $controller(
            $this->request('/telegram/bot-map/support/getMe', 'support', 'getMe', '', 'sekret'),
            'support',
            'getMe',
        );

        self::assertSame(502, $response->getStatusCode());
        self::assertStringContainsString('telegram exploded', $response->getContent());
    }

    public function test_macro_registers_route_telegram_bot_api(): void
    {
        $router = new Router(new Dispatcher(), new Container());

        BotMapController::macro($router);

        self::assertTrue($router->hasMacro('telegramBotApi'));

        $route = $router->telegramBotApi();
        self::assertInstanceOf(Route::class, $route);
        self::assertSame('telegram/bot-map/{bot}/{method}', $route->uri());
        self::assertSame(['POST'], $route->methods());
    }

    /**
     * @param list<RecordingBotClient> $clients
     */
    private function map(array $script, array &$clients): BotMap
    {
        return new BotMap(null, null, static function (string $token, array $config) use ($script, &$clients): RecordingBotClient {
            $client = new RecordingBotClient($token, $script);
            $clients[] = $client;

            return $client;
        });
    }

    private function request(string $uri, string $bot, string $method, string $body, string $secret): Request
    {
        return Request::create($uri, 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_BOTMAP_SIGNATURE' => hash_hmac('sha256', $bot . "\n" . $method . "\n" . $body, $secret),
        ], $body);
    }

    /** @return array{ok: bool, result: mixed} */
    private static function ok(mixed $result): array
    {
        return ['ok' => true, 'result' => $result];
    }
}