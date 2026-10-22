<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use Illuminate\Redis\Connections\Connection as IlluminateConnection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisConnection;
use LogicException;
use MeRezaRezaei\Teleframe\Bus\LaravelRedisAdapter;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Predis\Command\Redis\XADD;
use Predis\Command\Redis\XAUTOCLAIM;
use ReflectionMethod;

/**
 * Argument-shape and reply-shape guard for the stream verbs the Redis
 * contract gained (xadd options, xpending, xautoclaim, xrange, xdel, hdel,
 * command). No server: the underlying illuminate connection is mocked, so
 * every assertion is on the exact argv the adapter hands to the driver and
 * on the value it hands back.
 *
 * The two drivers genuinely disagree (predis xadd(stream, fields, id) vs
 * phpredis xadd(stream, id, fields); predis XACK/XDEL need the ids spread,
 * phpredis takes one array argument), so both branches are asserted, not
 * just one.
 *
 * XADD's trim is the load-bearing case here: the two drivers disagree on
 * the SHAPE, not just the order — predis wants an options structure while
 * phpredis wants typed positional parameters. Both the argv the adapter
 * hands over AND the wire form each driver derives from it are asserted,
 * on both drivers, because a mocked-command assertion alone cannot see a
 * trim being silently dropped.
 */
final class LaravelRedisAdapterStreamVerbsTest extends TestCase
{
    /** Frozen wire names — asserted verbatim so a rename cannot slip through. */
    private const STREAM = 'tg:stream:updates';

    private const GROUP = 'teleclient';

    private const DEAD_LETTER = 'tg:stream:dead-letter';

    /** @var list<array{0: string, 1: list<mixed>}> */
    private array $calls = [];

    private mixed $reply = null;

    // ------------------------------------------------------------- xadd

    public function test_xadd_maxlen_accepts_a_numeric_string_and_keeps_it_an_int(): void
    {
        $this->reply = '1-0';

        // Laravel config and env values arrive as strings. The cast is the
        // contract: both drivers must receive an INT, not '1000', because
        // phpredis declares the parameter `int` and predis splices the value
        // into argv where a quoted string changes the wire form.
        $this->predis()->xadd(self::STREAM, '*', ['a' => '1'], ['maxlen' => '1000']);
        $this->phpredis()->xadd(self::STREAM, '*', ['a' => '1'], ['maxlen' => '1000']);

        self::assertSame(
            [
                ['xadd', [self::STREAM, ['a' => '1'], '*', ['trim' => ['MAXLEN', '~', 1000]]]],
                ['xadd', [self::STREAM, '*', ['a' => '1'], 1000, true]],
            ],
            $this->calls,
        );
    }

    public function test_xadd_predis_puts_fields_before_the_id(): void
    {
        $this->reply = '1-0';
        $adapter = $this->predis();

        $this->assertSame('1-0', $adapter->xadd(self::STREAM, '1-0', ['a' => '1', 'b' => '2']));
        $this->assertSame(
            [['xadd', [self::STREAM, ['a' => '1', 'b' => '2'], '1-0']]],
            $this->calls,
        );
    }

    public function test_xadd_phpredis_puts_the_id_before_the_fields(): void
    {
        $this->reply = '1-0';
        $adapter = $this->phpredis();

        $this->assertSame('1-0', $adapter->xadd(self::STREAM, '1-0', ['a' => '1', 'b' => '2']));
        $this->assertSame(
            [['xadd', [self::STREAM, '1-0', ['a' => '1', 'b' => '2']]]],
            $this->calls,
        );
    }

    public function test_xadd_without_options_is_the_pre_existing_three_arg_form(): void
    {
        $this->reply = '1-0';
        // Backward compatibility: the plain form must not grow, reorder or
        // stringify anything, on either driver.
        $this->predis()->xadd(self::STREAM, '*', ['account_id' => '42']);
        $this->phpredis()->xadd(self::STREAM, '*', ['account_id' => '42']);

        $this->assertSame(
            [
                ['xadd', [self::STREAM, ['account_id' => '42'], '*']],
                ['xadd', [self::STREAM, '*', ['account_id' => '42']]],
            ],
            $this->calls,
        );
    }

    public function test_xadd_maxlen_emits_the_approximate_trim_on_both_drivers(): void
    {
        $this->reply = '1-0';
        $this->predis()->xadd(self::STREAM, '*', ['a' => '1'], ['maxlen' => 1000]);
        $this->phpredis()->xadd(self::STREAM, '*', ['a' => '1'], ['maxlen' => 1000]);

        $this->assertSame(
            [
                // predis: the trim is an OPTIONS STRUCTURE at argv index 3, not
                // a literal 'MAXLEN' token in argv — see the wire-form test
                // below, which runs the real Predis XADD class over this argv.
                ['xadd', [self::STREAM, ['a' => '1'], '*', ['trim' => ['MAXLEN', '~', 1000]]]],
                // phpredis: fixed-arity xAdd(key, id, values, maxlen, approx),
                // so the count is an int at index 3 and `~` is the bool at 4.
                ['xadd', [self::STREAM, '*', ['a' => '1'], 1000, true]],
            ],
            $this->calls,
        );
    }

    /**
     * The wire-form gate for XADD on the predis driver.
     *
     * Every other test here asserts only the argv the adapter HANDS OVER,
     * because connection() stubs command() — predis's own setArguments()
     * never runs and a wire-invalid argv is indistinguishable from a
     * wire-valid one. That is exactly how the untrimmed-stream defect
     * shipped green: a literal 'MAXLEN' at index 3 is silently DROPPED by
     * XADD::setArguments(), so a mocked-command assertion happily passed
     * while the real stream grew without bound.
     *
     * This feeds the real adapter argv through the real Predis command class
     * and asserts the exact tokens that go on the wire, MAXLEN and the count
     * included.
     */
    public function test_xadd_maxlen_argv_expands_to_the_wire_form_predis_actually_sends(): void
    {
        $this->reply = '1-0';
        $this->predis()->xadd(self::STREAM, '*', ['a' => '1'], ['maxlen' => 1000]);

        $command = new XADD;
        $command->setArguments($this->calls[0][1]);

        self::assertSame(
            [self::STREAM, 'MAXLEN', '~', 1000, '*', 'a', '1'],
            $command->getArguments(),
            'predis emits MAXLEN, the approximate marker and the count, BEFORE the id',
        );
    }

    /**
     * The same wire-form gate for the phpredis driver.
     *
     * phpredis has no command class to re-parse argv — Redis::xAdd() is a
     * FIXED-ARITY method with a TYPED signature, so a wrong argv cannot be
     * "expanded" at all: it is either bound to the right parameter or it
     * throws. Reflection is the honest proxy for "what the driver receives",
     * and it fails on a regression that a mere argv snapshot would accept.
     */
    public function test_xadd_maxlen_argv_binds_to_the_typed_phpredis_xadd_signature(): void
    {
        $this->reply = '1-0';
        $this->phpredis()->xadd(self::STREAM, '*', ['a' => '1'], ['maxlen' => 1000]);

        $argv = $this->calls[0][1];

        self::assertTrue(
            extension_loaded('redis'),
            'ext-redis is required to assert the phpredis wire signature',
        );

        $signature = (new ReflectionMethod('Redis', 'xAdd'))->getParameters();

        // key, id, values, maxlen, approx — a literal 'MAXLEN' cannot bind to
        // argument #4, which is declared `int`.
        self::assertCount(5, $argv, 'xAdd takes key, id, values, maxlen and approx');
        self::assertSame(self::STREAM, $argv[0]);
        self::assertSame('*', $argv[1]);
        self::assertSame(['a' => '1'], $argv[2]);
        self::assertIsInt($argv[3], 'maxlen is typed int: a literal MAXLEN token raises a TypeError');
        self::assertSame(1000, $argv[3]);
        self::assertTrue($argv[4], 'approx=true is what puts the ~ marker on the wire');

        // Same arguments, bound against the real signature.
        self::assertSame('string', (string) $signature[0]->getType());
        self::assertSame('string', (string) $signature[1]->getType());
        self::assertSame('array', (string) $signature[2]->getType());
        self::assertSame('int', (string) $signature[3]->getType());
        self::assertSame('bool', (string) $signature[4]->getType());
    }

    public function test_xadd_maxlen_of_zero_or_absent_never_trims(): void
    {
        $this->reply = '1-0';
        // maxlen 0 is "no bound", not "trim to nothing" — it must fall
        // through to the plain three-arg form.
        $this->predis()->xadd(self::STREAM, '*', ['a' => '1'], ['maxlen' => 0]);
        $this->assertSame([['xadd', [self::STREAM, ['a' => '1'], '*']]], $this->calls);
    }

    public function test_xadd_returns_empty_string_when_the_driver_does_not_answer_an_id(): void
    {
        $this->reply = false;
        $this->assertSame('', $this->predis()->xadd(self::STREAM, '*', ['a' => '1']));
    }

    // ------------------------------------------------------------- xpending

    public function test_xpending_summary_form_sends_only_stream_and_group(): void
    {
        $this->reply = [3, '1-0', '3-0', [['c1', '2']]];

        $this->assertSame([3, '1-0', '3-0', [['c1', '2']]], $this->predis()->xpending(self::STREAM, self::GROUP));
        $this->assertSame([['xpending', [self::STREAM, self::GROUP]]], $this->calls);
    }

    public function test_xpending_extended_form_defaults_start_to_dash(): void
    {
        $this->reply = [1, '2-0', '2-0', [['c1', '1']]];

        $this->predis()->xpending(self::STREAM, self::GROUP, null, '+', 7);
        $this->assertSame([['xpending', [self::STREAM, self::GROUP, '-', '+', 7]]], $this->calls);
    }

    public function test_xpending_extended_form_defaults_end_to_plus(): void
    {
        $this->predis()->xpending(self::STREAM, self::GROUP, '1-0');
        $this->assertSame([['xpending', [self::STREAM, self::GROUP, '1-0', '+', 100]]], $this->calls);
    }

    public function test_xpending_extended_form_keeps_both_bounds_and_count(): void
    {
        $this->predis()->xpending(self::STREAM, self::GROUP, '1-0', '9-0', 25);
        $this->assertSame([['xpending', [self::STREAM, self::GROUP, '1-0', '9-0', 25]]], $this->calls);
    }

    public function test_xpending_strips_the_phpredis_message_key_and_reindexes(): void
    {
        // phpredis answers the summary form as an assoc array whose 'message'
        // slot holds the idle-consumer note. Callers read positionally, so
        // the key must be gone and indices 0..n-1 must be intact.
        $this->reply = [
            'message' => '2 consumers idle for too long',
            0 => 4,
            1 => '5-0',
            2 => '9-0',
            3 => [['c1', '4']],
        ];

        $this->assertSame(
            [4, '5-0', '9-0', [['c1', '4']]],
            $this->phpredis()->xpending(self::STREAM, self::GROUP),
        );
        self::assertArrayNotHasKey(
            'message',
            $this->phpredis()->xpending(self::STREAM, self::GROUP),
        );
    }

    public function test_xpending_predis_positional_reply_is_untouched(): void
    {
        $this->reply = [0, null, null, []];

        self::assertSame([0, null, null, []], $this->predis()->xpending(self::STREAM, self::GROUP));
    }

    public function test_xpending_non_array_reply_becomes_an_empty_array(): void
    {
        $this->reply = false;

        self::assertSame([], $this->predis()->xpending(self::STREAM, self::GROUP));
    }

    public function test_xpending_reindexes_a_non_contiguous_reply(): void
    {
        $this->reply = [
            'message' => '2 consumers idle for too long',
            5 => 'first',
            7 => 'second',
        ];

        self::assertSame(['first', 'second'], $this->phpredis()->xpending(self::STREAM, self::GROUP));
    }

    // ----------------------------------------------------------- xautoclaim

    public function test_xautoclaim_sends_the_same_argv_on_both_drivers(): void
    {
        $this->predis()->xautoclaim(self::STREAM, self::GROUP, 'c2', 60000);
        $this->phpredis()->xautoclaim(self::STREAM, self::GROUP, 'c2', 60000);

        // SIX positional arguments. The literal token 'COUNT' must NOT be one
        // of them: predis splices the first five and then pushes its OWN
        // 'COUNT' followed by the sixth, so a caller-supplied 'COUNT' landed
        // at index 0 of the tail and put a doubled COUNT on the wire, which
        // Redis rejects.
        $this->assertSame(
            [
                ['xautoclaim', [self::STREAM, self::GROUP, 'c2', '60000', '0-0', 100]],
                ['xautoclaim', [self::STREAM, self::GROUP, 'c2', '60000', '0-0', 100]],
            ],
            $this->calls,
        );

        foreach ($this->calls as [, $argv]) {
            self::assertCount(6, $argv, 'XAUTOCLAIM takes five positional args plus the count');
            foreach ($argv as $token) {
                if (is_string($token)) {
                    self::assertNotSame('COUNT', strtoupper($token), 'the driver emits COUNT itself');
                }
            }
            self::assertSame(100, $argv[5], 'the count is the sixth argument, unquoted');
        }
    }

    public function test_xautoclaim_honours_an_explicit_start_and_count(): void
    {
        $this->predis()->xautoclaim(self::STREAM, self::GROUP, 'c2', 1000, '42-0', 10);

        $this->assertSame(
            [['xautoclaim', [self::STREAM, self::GROUP, 'c2', '1000', '42-0', 10]]],
            $this->calls,
        );

        $argv = $this->calls[0][1];
        self::assertCount(6, $argv);
        self::assertNotContains('COUNT', $argv, 'the driver emits COUNT itself');
        self::assertSame(10, $argv[5], 'the requested count, not the default, is the sixth argument');
    }

    /**
     * The check that was missing when the doubled-COUNT defect shipped green.
     *
     * Every other test here asserts only the argv the adapter HANDS OVER,
     * because connection() stubs command() — so predis's own
     * setArguments() never runs and a wire-invalid argv is indistinguishable
     * from a wire-valid one. This test closes that gap by feeding the real
     * adapter argv through the real Predis command class and asserting the
     * exact wire form. It fails the moment the adapter regresses to passing a
     * literal 'COUNT'.
     */
    public function test_xautoclaim_argv_expands_to_the_wire_form_predis_actually_sends(): void
    {
        $this->reply = ['0-0', [], []];
        $this->predis()->xautoclaim(self::STREAM, self::GROUP, 'c2', 60000, '42-0', 10);

        $command = new XAUTOCLAIM;
        $command->setArguments($this->calls[0][1]);

        self::assertSame(
            [self::STREAM, self::GROUP, 'c2', '60000', '42-0', 'COUNT', 10],
            $command->getArguments(),
            'predis supplies exactly one COUNT, immediately before the count',
        );
    }

    public function test_xautoclaim_returns_the_predis_resp2_shape_unnormalised(): void
    {
        $resp2 = ['0-0', [['5-0', ['u', '1']]], []];

        $this->reply = $resp2;
        $this->assertSame($resp2, $this->predis()->xautoclaim(self::STREAM, self::GROUP, 'c2', 60000));
    }

    public function test_xautoclaim_returns_the_phpredis_associative_shape_unnormalised(): void
    {
        // Deliberately NOT reshaped into the RESP2 triple: normalizeEntries()
        // accepts both, and flattening here would mislabel the driver.
        $assoc = ['next' => '0-0', 'entries' => [['5-0', ['u', '1']]], 'deleted' => []];

        $this->reply = $assoc;
        $this->assertSame($assoc, $this->phpredis()->xautoclaim(self::STREAM, self::GROUP, 'c2', 60000));
    }

    public function test_xautoclaim_non_array_reply_becomes_an_empty_array(): void
    {
        $this->reply = null;

        self::assertSame([], $this->predis()->xautoclaim(self::STREAM, self::GROUP, 'c2', 1));
    }

    // --------------------------------------------------------------- xrange

    public function test_xrange_sends_bounds_then_count_and_returns_entries_verbatim(): void
    {
        $entries = ['1-0' => ['k' => 'v']];
        $this->reply = $entries;

        self::assertSame(
            $entries,
            $this->predis()->xrange(self::DEAD_LETTER, '0', '+', 50),
        );
        $this->assertSame(
            [['xrange', [self::DEAD_LETTER, '0', '+', 'COUNT', '50']]],
            $this->calls,
        );
    }

    public function test_xrange_defaults_to_count_100_and_the_phpredis_shape_matches(): void
    {
        $this->phpredis()->xrange(self::DEAD_LETTER, '-', '+');
        $this->assertSame(
            [['xrange', [self::DEAD_LETTER, '-', '+', 'COUNT', '100']]],
            $this->calls,
        );
    }

    public function test_xrange_non_array_reply_becomes_an_empty_array(): void
    {
        $this->reply = false;

        self::assertSame([], $this->predis()->xrange(self::DEAD_LETTER, '0', '+'));
    }

    // ----------------------------------------------------------------- xdel

    public function test_xdel_predis_spreads_the_ids(): void
    {
        $this->reply = 2;

        self::assertSame(2, $this->predis()->xdel(self::DEAD_LETTER, ['1-0', '2-0']));
        $this->assertSame(
            [['xdel', [self::DEAD_LETTER, '1-0', '2-0']]],
            $this->calls,
        );
    }

    public function test_xdel_phpredis_passes_the_ids_as_one_array_argument(): void
    {
        $this->reply = 2;

        self::assertSame(2, $this->phpredis()->xdel(self::DEAD_LETTER, ['1-0', '2-0']));
        $this->assertSame(
            [['xdel', [self::DEAD_LETTER, ['1-0', '2-0']]]],
            $this->calls,
        );
    }

    public function test_xdel_with_no_ids_issues_no_command_and_returns_zero(): void
    {
        self::assertSame(0, $this->predis()->xdel(self::DEAD_LETTER, []));
        self::assertSame(0, $this->phpredis()->xdel(self::DEAD_LETTER, []));

        self::assertSame([], $this->calls, 'an empty id list must not reach the wire');
    }

    public function test_xdel_casts_a_string_reply_to_int(): void
    {
        $this->reply = '3';

        self::assertSame(3, $this->predis()->xdel(self::DEAD_LETTER, ['1-0']));
    }

    // ----------------------------------------------------------------- hdel

    public function test_hdel_spreads_the_fields_after_the_key_on_both_drivers(): void
    {
        $this->reply = 2;

        self::assertSame(2, $this->predis()->hdel('tg:bus:strikes', 'a:1', 'b:2'));
        self::assertSame(2, $this->phpredis()->hdel('tg:bus:strikes', 'a:1', 'b:2'));

        $this->assertSame(
            [
                ['hdel', ['tg:bus:strikes', 'a:1', 'b:2']],
                ['hdel', ['tg:bus:strikes', 'a:1', 'b:2']],
            ],
            $this->calls,
        );
    }

    public function test_hdel_with_no_fields_issues_no_command_and_returns_zero(): void
    {
        self::assertSame(0, $this->predis()->hdel('tg:bus:strikes'));
        self::assertSame(0, $this->phpredis()->hdel('tg:bus:strikes'));

        self::assertSame([], $this->calls);
    }

    // --------------------------------------------------------- xreadgroup

    public function test_xreadgroup_predis_sends_one_count_and_the_stream_pair(): void
    {
        $this->reply = [[
            self::STREAM,
            [['1-0', ['account_id', '42', 'seq', '1']]],
        ]];

        $read = $this->predis()->xreadgroup(self::GROUP, 'c1', [self::STREAM => 10]);

        self::assertSame(
            [['xreadgroup', [self::GROUP, 'c1', 10, null, false, self::STREAM, '>']]],
            $this->calls,
        );
        self::assertSame([self::STREAM => ['1-0' => ['account_id' => '42', 'seq' => '1']]], $read);
    }

    public function test_xreadgroup_phpredis_keys_each_stream_by_its_min_id(): void
    {
        $this->reply = [self::STREAM => ['1-0' => ['account_id' => '42']]];

        $read = $this->phpredis()->xreadgroup(self::GROUP, 'c1', [self::STREAM => 10]);

        self::assertSame(
            [['xreadgroup', [self::GROUP, 'c1', [self::STREAM => '>'], 10]]],
            $this->calls,
        );
        self::assertSame([self::STREAM => ['1-0' => ['account_id' => '42']]], $read);
    }

    public function test_xreadgroup_phpredis_preserves_every_stream_in_a_multi_stream_reply(): void
    {
        $this->reply = [
            'pfx-'.self::STREAM => ['1-0' => ['account_id' => '42']],
            'pfx-other' => ['2-0' => ['account_id' => '7']],
        ];

        $read = $this->phpredis()->xreadgroup(self::GROUP, 'c1', [self::STREAM => 10, 'other' => 10]);

        self::assertSame(
            [
                self::STREAM => ['1-0' => ['account_id' => '42']],
                'other' => ['2-0' => ['account_id' => '7']],
            ],
            $read,
        );
    }

    public function test_xreadgroup_with_no_streams_asks_for_zero(): void
    {
        $this->predis()->xreadgroup(self::GROUP, 'c1', []);
        $this->phpredis()->xreadgroup(self::GROUP, 'c1', []);

        self::assertSame(
            [
                ['xreadgroup', [self::GROUP, 'c1', 0, null, false]],
                ['xreadgroup', [self::GROUP, 'c1', [], 0]],
            ],
            $this->calls,
        );
    }

    public function test_xreadgroup_maps_a_prefixed_reply_to_the_longest_requested_name(): void
    {
        // Both requested names are suffixes of the physical reply. The
        // LONGEST match wins — a shortest-match implementation would key the
        // reply 'updates' and the consumer would never find its stream.
        $this->reply = [[
            'pfx-'.self::STREAM,
            [['1-0', ['account_id', '42']]],
        ]];

        $read = $this->predis()->xreadgroup(self::GROUP, 'c1', ['updates' => 10, self::STREAM => 10]);

        self::assertSame([self::STREAM => ['1-0' => ['account_id' => '42']]], $read);
    }

    public function test_xreadgroup_keeps_a_physical_name_that_matches_no_requested_name(): void
    {
        $this->reply = [['pfx-other-stream', [['1-0', ['account_id', '42']]]]];

        $read = $this->predis()->xreadgroup(self::GROUP, 'c1', [self::STREAM => 10]);

        self::assertSame(['pfx-other-stream' => ['1-0' => ['account_id' => '42']]], $read);
    }

    public function test_xreadgroup_ignores_a_blank_requested_name_when_matching(): void
    {
        // '' is not a stream: allowing it to "match" would make every reply
        // key the empty string, hiding the stream the caller asked for.
        $this->reply = [['pfx-'.self::STREAM, [['1-0', ['account_id', '42']]]]];

        $read = $this->predis()->xreadgroup(self::GROUP, 'c1', ['' => 10, self::STREAM => 10]);

        self::assertSame([self::STREAM => ['1-0' => ['account_id' => '42']]], $read);
    }

    // ---------------------------------------------------------------- xack

    public function test_xack_predis_spreads_the_ids(): void
    {
        $this->reply = 2;

        self::assertSame(2, $this->predis()->xack(self::STREAM, self::GROUP, ['1-0', '2-0']));
        self::assertSame(
            [['xack', [self::STREAM, self::GROUP, '1-0', '2-0']]],
            $this->calls,
        );
    }

    public function test_xack_phpredis_passes_the_ids_as_one_array_argument(): void
    {
        $this->reply = 2;

        self::assertSame(2, $this->phpredis()->xack(self::STREAM, self::GROUP, ['1-0', '2-0']));
        self::assertSame(
            [['xack', [self::STREAM, self::GROUP, ['1-0', '2-0']]]],
            $this->calls,
        );
    }

    public function test_xack_casts_a_string_reply_to_int(): void
    {
        $this->reply = '2';

        $acked = $this->predis()->xack(self::STREAM, self::GROUP, ['1-0']);

        self::assertSame(2, $acked);
        self::assertIsInt($acked, 'XACK must return int even when the driver answers a string');
    }

    // ------------------------------------------------------- subscribe text

    public function test_subscribe_refusal_message_is_pinned_verbatim(): void
    {
        try {
            $this->predis()->subscribe('chan', static function (): void {});
        } catch (LogicException $e) {
            self::assertSame(
                'LaravelRedisAdapter cannot honor the synchronous loopback subscribe() semantic; '
                .'bind the in-memory double in tests and use a dedicated pub/sub loop in daemons.',
                $e->getMessage(),
            );

            return;
        }

        self::fail('subscribe() must refuse on the production adapter.');
    }

    // -------------------------------------------------------------- command

    public function test_command_forwards_name_and_argv_untouched(): void
    {
        $this->reply = 'OK';

        self::assertSame(
            'OK',
            $this->predis()->command('xgroup', ['CREATE', self::STREAM, self::GROUP, '0', true]),
        );
        $this->assertSame(
            [['xgroup', ['CREATE', self::STREAM, self::GROUP, '0', true]]],
            $this->calls,
        );
    }

    // -------------------------------------------------------------- doubles

    private function predis(): LaravelRedisAdapter
    {
        return new LaravelRedisAdapter($this->connection(PredisConnection::class));
    }

    private function phpredis(): LaravelRedisAdapter
    {
        return new LaravelRedisAdapter($this->connection(PhpRedisConnection::class));
    }

    private function connection(string $class): IlluminateConnection
    {
        /** @var IlluminateConnection&Stub $connection */
        $connection = $this->createStub($class);
        $connection->method('command')
            ->willReturnCallback(function (string $method, array $parameters = []) {
                $this->calls[] = [$method, $parameters];

                return $this->reply;
            });

        return $connection;
    }


    public function test_pair_ignores_trailing_odd_element(): void
    {
        $class = new \ReflectionClass(\MeRezaRezaei\Teleframe\Bus\LaravelRedisAdapter::class);
        $method = $class->getMethod('pair');
        $method->setAccessible(true);

        self::assertSame(['a' => '1'], $method->invoke(null, ['a', '1', 'b']));
    }
}
