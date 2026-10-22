<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel;

use Illuminate\Http\Client\Factory;
use MeRezaRezaei\Teleframe\Bot\Services\BotClient;
use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;
use MeRezaRezaei\Teleframe\Laravel\Services\UpdatePollerService;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Throwable;

/**
 * THE POLLER MUST NOT ADVANCE ITS OFFSET WITHOUT AN UPDATE.
 *
 * The `offset` parameter is how the Bot API is told what you already have. Once
 * it moves past an update that was never delivered, that update is gone — the
 * server will not resend it. So anything that can move the offset without a real
 * update is permanent, silent data loss.
 *
 * Two shapes did exactly that, both measured against the real code before this
 * test was written:
 *
 *  - `BotClient` answers a 200-with-empty-body `getUpdates` with a synthetic
 *    `bot_result`, whose `result` is an ASSOC array
 *    `{_: 'bot_result', method: 'getUpdates', params: [...]}`. Iterated as a
 *    list of updates, its `params` entry is an array with no `update_id`, so
 *    `(int) null` evaluated to 0 and `max($offset, 0 + 1)` moved the cursor to
 *    1 having received nothing — dropping update 0 forever.
 *  - Any other malformed `result` reached the same arithmetic.
 *
 * This drives the REAL `pollBot()`. That loop is a `while ($this->running)`, so
 * it needs a bounded exit: the second HTTP call is answered with a 401, which the
 * loop's own error policy treats as terminal and rethrows. The first response is
 * the one under test. An earlier draft of this file re-implemented the iteration
 * inside the test, which would have proved nothing about the production code.
 */
final class PollerOffsetIntegrityTest extends TestCase
{
    private const TOKEN = '12345';

    public function test_a_synthetic_bot_result_cannot_advance_the_offset(): void
    {
        $offset = $this->offsetAfterOneResponse([
            '_' => 'bot_result',
            'method' => 'getUpdates',
            'params' => ['offset' => 0, 'limit' => 100, 'timeout' => 30],
        ]);

        $this->assertSame(
            0,
            $offset,
            'the offset moved without a single update being delivered — update 0 is now unrecoverable',
        );
    }

    public function test_a_malformed_result_containing_an_array_entry_also_cannot_advance_the_offset(): void
    {
        // The precise shape that broke it: an assoc `result` whose `params`
        // value is an array, and so looks update-shaped to a naive cast.
        $offset = $this->offsetAfterOneResponse([
            '_' => 'bot_result',
            'method' => 'getUpdates',
            'params' => [['chat_id' => 1, 'text' => 'not an update']],
        ]);

        $this->assertSame(0, $offset, 'an array entry with no update_id must not move the cursor');
    }

    public function test_a_non_numeric_update_id_is_refused(): void
    {
        $offset = $this->offsetAfterOneResponse([
            ['update_id' => 'abc', 'message' => ['message_id' => 1]],
        ]);

        $this->assertSame(0, $offset, 'a non-numeric update_id cannot be a Telegram update');
    }

    public function test_a_real_update_still_advances_the_offset(): void
    {
        $offset = $this->offsetAfterOneResponse([
            ['update_id' => 41, 'message' => ['message_id' => 1]],
        ]);

        $this->assertSame(42, $offset, 'a real update must still advance the cursor — the guard must not freeze the poller');
    }

    public function test_a_string_update_id_is_accepted_because_telegram_sends_them_as_strings(): void
    {
        $offset = $this->offsetAfterOneResponse([
            ['update_id' => '7', 'message' => ['message_id' => 1]],
        ]);

        $this->assertSame(8, $offset, 'a numeric-string update_id is a real update');
    }

    public function test_a_malformed_entry_does_not_discard_the_real_ones_around_it(): void
    {
        $offset = $this->offsetAfterOneResponse([
            ['update_id' => 5, 'message' => ['message_id' => 1]],
            ['no_update_id' => true],
            ['update_id' => 9, 'message' => ['message_id' => 2]],
        ]);

        $this->assertSame(10, $offset, 'a malformed entry must not discard the real updates around it');
    }

    public function test_a_real_update_actually_reaches_the_sink(): void
    {
        // The offset guard must not also swallow genuine updates.
        $http = new Factory;
        $http->fake(function () use ($http) {
            static $calls = 0;
            $calls++;

            return $calls === 1
                ? $http->response(['ok' => true, 'result' => [['update_id' => 3, 'message' => ['message_id' => 1]]]], 200)
                : $http->response(['ok' => false, 'error_code' => 401, 'description' => 'Unauthorized'], 401);
        });

        $poller = new UpdatePollerService;
        $seen = [];

        try {
            $poller->pollBot(new BotClient(self::TOKEN, http: $http), 1, 10, function (array $u) use (&$seen, $poller): void {
                $seen[] = $u['update_id'];
                $poller->stop();
            });
        } catch (Throwable) {
            // the terminal 401 ends the loop; that is the harness, not the test
        }

        $this->assertSame([3], $seen, 'a genuine update must still be delivered to the sink');
    }

    // ---- harness ---------------------------------------------------------

    /**
     * Run the real pollBot for exactly ONE response, end it with a terminal
     * 401, and report the `offset` the loop sent on the FOLLOWING request.
     *
     * `offset` is a local inside pollBot, so it cannot be read back. It does
     * not need to be: the offset is a request parameter, and the second
     * getUpdates carries whatever the loop settled on. Reading it off the
     * recorded request observes the real production arithmetic through the real
     * wire, instead of a copy of it.
     *
     * @param  array<string, mixed>  $result
     */
    private function offsetAfterOneResponse(array $result): int
    {
        $http = new Factory;
        $http->fake(function () use ($http, $result) {
            static $calls = 0;
            $calls++;

            return $calls === 1
                ? $http->response(['ok' => true, 'result' => $result], 200)
                : $http->response(['ok' => false, 'error_code' => 401, 'description' => 'Unauthorized'], 401);
        });

        $poller = new UpdatePollerService;

        try {
            $poller->pollBot(new BotClient(self::TOKEN, http: $http), 1, 10, static function (): void {});
        } catch (Throwable) {
            // The terminal 401 is how this harness ends an infinite loop.
        }

        $recorded = $http->recorded();
        $this->assertGreaterThanOrEqual(2, $recorded->count(), 'harness: expected a second getUpdates to observe');

        /** @var array<string, mixed> $second */
        $second = $recorded[1][0]->data();

        return (int) ($second['offset'] ?? -1);
    }
}
