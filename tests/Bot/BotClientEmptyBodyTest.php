<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bot;

use Illuminate\Http\Client\Factory;
use MeRezaRezaei\Teleframe\Bot\Services\BotClient;
use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;
use PHPUnit\Framework\TestCase;

/**
 * AN EMPTY RESPONSE BODY IS NOT A DELIVERED MESSAGE.
 *
 * `BotClient::call()` reported `ok => true` with a synthetic `bot_result`
 * whenever the response body was empty. Laravel's HTTP client does not throw on
 * 5xx, so a gateway 502, a proxy hop that returned nothing, or a TLS read
 * truncated mid-response all arrived with an empty body — and every one of them
 * was reported to the host as a success.
 *
 * The consequences were not cosmetic. `sendMessage` told a caller their message
 * was sent when nothing was delivered, and `UpdatePollerService::pollBot()`
 * advanced its `offset` past an update it never received, so a single 502 could
 * silently drop one update from a bot's whole stream.
 *
 * Telegram answers 200 with a JSON body for every real call, so an empty body
 * on a non-2xx is never a success.
 */
final class BotClientEmptyBodyTest extends TestCase
{
    private const TOKEN = '12345';

    private const SEND = 'https://api.telegram.org/bot12345/sendMessage';

    public function test_a_502_with_an_empty_body_is_not_reported_as_success(): void
    {
        $http = new Factory;
        $http->fake([self::SEND => $http->response('', 502)]);

        $bot = new BotClient(self::TOKEN, http: $http);

        $this->expectException(TelegramException::class);
        $this->expectExceptionMessageMatches('/\[502\]/');

        $bot->sendMessage('@channel', 'this will not be delivered');
    }

    public function test_a_200_with_an_empty_body_is_not_reported_as_success(): void
    {
        // A 200 with an empty body is NOT a valid Telegram response.
        // Telegram answers 200 with a JSON body for every real call.
        // An empty body on 200 is a gateway/proxy failure, not a success.
        $http = new Factory;
        $http->fake([self::SEND => $http->response('', 200)]);

        $bot = new BotClient(self::TOKEN, http: $http);

        $this->expectException(TelegramException::class);
        $this->expectExceptionMessageMatches('/\[200\]/');

        $bot->sendMessage('@channel', 'empty body is not success');
    }

    public function test_a_normal_200_with_a_body_is_unaffected(): void
    {
        $http = new Factory;
        $http->fake([
            self::SEND => $http->response(
                ['ok' => true, 'result' => ['message_id' => 7]],
                200,
            ),
        ]);

        $bot = new BotClient(self::TOKEN, http: $http);
        $result = $bot->sendMessage('@channel', 'hello');

        $this->assertTrue($result['ok']);
        $this->assertSame(7, $result['result']['message_id']);
    }

    /**
     * The `retry(..., throw: false)` seam. `BotClient::call()` wraps the
     * request in `retry($this->retries, 100, throw: false)` when retries are
     * configured, and `throw: false` is the exact reason the old branch was
     * reachable: Laravel's retry helper only throws on the FINAL attempt when
     * `retryThrow` is true (PendingRequest::sendResponse(), the
     * `if ($potentialTries > 1 && $this->retryThrow)` guard). With
     * `throw: false` a 502 that is retried to exhaustion is RETURNED to
     * `call()` as an ordinary response — empty body, status 502 — and before
     * the fix that became a fabricated `bot_result`.
     *
     * So the retry path is not merely unaffected by the gate: it is the path
     * the gate had to be proven on, because it is the only configuration
     * where a 5xx arrives at the branch as a normal return value rather than
     * as a thrown `RequestException`.
     */
    public function test_a_502_that_exhausts_the_retry_budget_throws_rather_than_fabricating_success(): void
    {
        $http = new Factory;
        $http->fake([self::SEND => $http->response('', 502)]);

        // retries: 3 => three total attempts, all answered 502 by the fake.
        $bot = new BotClient(self::TOKEN, retries: 3, http: $http);

        try {
            $bot->sendMessage('@channel', 'retried, and still not delivered');
            $this->fail('a 502 retried to exhaustion must not be reported as success');
        } catch (TelegramException $e) {
            $this->assertSame(502, $e->getCode(), 'the real status must survive to the exception');
        }

        // Prove the retry path actually ran — otherwise this test would also
        // pass against a client that never retried, and would be asserting
        // nothing about the seam it is named for.
        $this->assertSame(3, count($http->recorded()), 'the retry budget must really have been spent');
    }

    /**
     * The same seam, the other end: a retried request that RECOVERS on a
     * later attempt returns that attempt's real body. The status gate must
     * not turn a recovered retry into a failure.
     */
    public function test_a_retried_request_that_recovers_returns_the_recovered_body(): void
    {
        $http = new Factory;
        $http->fake([
            self::SEND => $http->sequence()
                ->push('', 502)
                ->push(['ok' => true, 'result' => ['message_id' => 11]], 200),
        ]);

        $bot = new BotClient(self::TOKEN, retries: 3, http: $http);
        $result = $bot->sendMessage('@channel', 'delivered on the second attempt');

        $this->assertTrue($result['ok']);
        $this->assertSame(11, $result['result']['message_id'], 'the recovered attempt, not a bot_result');
    }

    public function test_a_401_still_reports_its_own_code_for_the_poller_to_act_on(): void
    {
        // The terminal-401 policy added to the poller depends on the real code
        // surviving to the exception. Check the seam still carries it.
        $http = new Factory;
        $http->fake([
            self::SEND => $http->response(
                ['ok' => false, 'error_code' => 401, 'description' => 'Unauthorized'],
                401,
            ),
        ]);

        $bot = new BotClient(self::TOKEN, http: $http);

        try {
            $bot->sendMessage('@channel', 'nope');
            $this->fail('a 401 must not be reported as success');
        } catch (TelegramException $e) {
            $this->assertSame(401, $e->getCode(), 'the poller keys its terminal decision off this code');
        }
    }
}
