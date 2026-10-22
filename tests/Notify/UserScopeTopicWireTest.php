<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLDecoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLRegistry;
use MeRezaRezaei\Teleframe\Core\Types\InputPeer;
use MeRezaRezaei\Teleframe\Notify\UserScopeTransport;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * THE TOPIC ACTUALLY LANDS — the test whose absence WAS the defect.
 *
 * `UserScopeTransport` sent the Bot API's `message_thread_id` as a top-level
 * `reply_to_msg_id` on `messages.sendMessage`. That constructor declares NO
 * such field: it declares `reply_to:flags.0?InputReplyTo`, and
 * `reply_to_msg_id` is a field of `inputReplyToMessage`, one level down. So
 * the shape was not wrong-typed, it was UNDECLARED — and that is the silent
 * kind of wrong. `TLEncoder::encodeObject` walks the DECLARED fields, finds
 * nothing named `reply_to_msg_id`, never sets `flags.0`, and drops the key
 * without a word: no exception, no log, `flags = 0`, `reply_to` absent. A
 * notification aimed at forum topic 3 arrived in General and every layer
 * above reported success.
 *
 * WHY THE OLD TESTS DID NOT CATCH IT, which is why this file looks like it
 * does. Three of them asserted `$params['reply_to_msg_id'] === 3` — the
 * params ARRAY the code built, which the buggy code did build. They were
 * green, and green, and green with the topic silently misfiled. A test that
 * only reads the array the code assembled cannot fail for a defect that is
 * about what leaves the process.
 *
 * So every assertion here goes one level lower, through the REAL registry
 * and the REAL encoder, and reads the BYTES:
 *  - the declared field names come from `TLRegistry::signatureOf()` at
 *    runtime, never from a copy of the .tl line;
 *  - the wire is produced by `TLEncoder::encodeObject()`;
 *  - `flags` is read off the encoded word, not off the params;
 *  - `reply_to` is asserted present AFTER a `TLDecoder` round-trip, i.e.
 *    from the bytes, and the decoder is the one that would report it absent.
 *
 * The last two tests are the FALSIFICATION, kept permanently rather than run
 * once and deleted: they build the old wrong shape and the flag-forced-clear
 * shape, encode both through the same real encoder, and assert the topic is
 * gone — silently in the first case, loudly in the second. A guard that has
 * never been shown to fail on the defect it guards is a rumour.
 *
 * HONEST LIMIT: this proves the encoded bytes, not Telegram's filing of them.
 * No live user session exists in this repo, so "the notification lands in
 * topic 3" remains unproven here — what is proven is that `reply_to` is on
 * the wire in the shape `messages.sendMessage` declares, which is the most
 * this repo can prove offline.
 */
final class UserScopeTopicWireTest extends TestCase
{
    /** The declared constructors whose wire shape is under test. */
    private const SEND = 'messages.sendMessage';

    private const SEND_MEDIA = 'messages.sendMedia';

    /**
     * THE SPEC LINE, read at runtime and never hard-coded.
     *
     * `src/Schema/schema/sources/api.tl:2495` (and `api_full.tl:2439`)
     * declare `messages.sendMessage#fef48f62 flags:# … peer:InputPeer
     * reply_to:flags.0?InputReplyTo message:string random_id:long …`. This
     * test asserts the registry agrees, and — the half that matters — that
     * the registry does NOT know a field called `reply_to_msg_id` on this
     * constructor. If a future schema re-snapshot ever added such a field,
     * this goes red and the transport's shape has to be re-argued, not
     * quietly inherited.
     */
    public function test_the_registry_declares_reply_to_and_knows_no_reply_to_msg_id_on_send_message(): void
    {
        self::assertSame('fef48f62', strtolower(dechex(TLRegistry::id(self::SEND))), 'api.tl:2495 constructor id');

        $replyTo = $this->field(self::SEND, 'reply_to');
        self::assertSame('flags', $replyTo['flagWord'], 'reply_to is guarded by a flag word');
        self::assertSame(0, $replyTo['bit'], 'api.tl:2495 — reply_to is flags.0?InputReplyTo');
        self::assertSame('InputReplyTo', $replyTo['type']);

        self::assertNull(
            $this->field(self::SEND, 'reply_to_msg_id'),
            'the top-level reply_to_msg_id the transport used to send is NOT a declared field — it is the defect this file exists for',
        );

        // `reply_to_msg_id` is real, one level down, and that is where the id belongs.
        $nested = $this->field('inputReplyToMessage', 'reply_to_msg_id');
        self::assertSame('int', $nested['type'], 'api.tl:1734 — inputReplyToMessage.reply_to_msg_id:int');
        self::assertSame('3bd4b7c2', strtolower(dechex(TLRegistry::id('inputReplyToMessage'))), 'api.tl:1734 constructor id');
    }

    /**
     * THE PROOF THAT MATTERS: encode the real transport's real params through
     * the real encoder, read `flags` off the wire, and decode the bytes back.
     */
    public function test_the_topic_is_on_the_wire_flags_bit_set_and_reply_to_present(): void
    {
        $wire = $this->sendMessageWire(3);

        $decoded = $this->roundTrip(self::SEND, $wire);

        self::assertSame(1, $decoded['flags'] & 1, 'flags.0 must be SET — a clear bit is the silent no-op');
        self::assertArrayHasKey('reply_to', $decoded, 'reply_to must be present on the wire, not merely in the params array');
        self::assertSame('inputReplyToMessage', $decoded['reply_to']['_']);
        self::assertSame(3, $decoded['reply_to']['reply_to_msg_id'], 'the topic id survived the encode/decode round trip');
        self::assertSame(0, $decoded['reply_to']['flags'] & 1, 'no top_msg_id: the contract is the topic, not a comment inside it');
        self::assertArrayNotHasKey('top_msg_id', $decoded['reply_to']);

        // The bytes themselves, not the decoded view: the nested constructor
        // id has to be physically present in the buffer. TL ids travel
        // little-endian, so it is the byte-reversed form that must appear.
        self::assertStringContainsString(
            $this->littleEndianHex(TLRegistry::id('inputReplyToMessage')),
            bin2hex($this->encoded(self::SEND, $wire)),
            'the inputReplyToMessage constructor id is literally in the encoded bytes',
        );
    }

    /** Without a topic the flag word is clear and `reply_to` is absent — the ordinary non-forum send still encodes. */
    public function test_a_send_with_no_topic_encodes_with_the_flag_bit_clear(): void
    {
        $wire = $this->sendMessageWire(null);
        $decoded = $this->roundTrip(self::SEND, $wire);

        self::assertSame(0, $decoded['flags'] & 1);
        self::assertArrayNotHasKey('reply_to', $decoded);
    }

    /** The attachment half of a notification has the same topic contract, and had the same drop. */
    public function test_an_attachment_send_also_carries_the_topic_on_the_wire(): void
    {
        $scope = new FakeUserScope;
        $transport = new UserScopeTransport(
            $scope->resolver(),
            peerResolver: static fn (): array => InputPeer::channel(-1001, '55555'),
            uploader: static fn (string $p, string $m, string $n): array => [
                '_' => 'inputMediaUploadedDocument',
                'mime_type' => $m,
                'file' => [
                    '_' => 'inputFile',
                    'id' => 0,
                    'parts' => 1,
                    'name' => $n,
                    'md5_checksum' => str_repeat("\x00", 16),
                ],
                'attributes' => [],
            ],
        );

        $file = sys_get_temp_dir().'/tframe-topic-wire-'.bin2hex(random_bytes(4)).'.html';
        file_put_contents($file, '<h1>report</h1>');

        try {
            $transport->call('sendDocument', [
                'chat_id' => -1001,
                'message_thread_id' => 7,
                'document' => $file,
                'mime' => 'text/html',
                'caption' => '[ALERT] report',
            ]);
        } finally {
            @unlink($file);
        }

        $decoded = $this->roundTrip(self::SEND_MEDIA, $scope->onlyParams());

        self::assertSame(1, $decoded['flags'] & 1, 'messages.sendMedia declares reply_to:flags.0?InputReplyTo too — api.tl — and the report must not fall into General');
        self::assertSame('inputReplyToMessage', $decoded['reply_to']['_']);
        self::assertSame(7, $decoded['reply_to']['reply_to_msg_id']);
    }

    /**
     * THE GENERALISED GUARD — every key the transport puts on the wire is a
     * DECLARED field of the constructor it is destined for. This is the shape
     * of the bug turned into an invariant, so the next undeclared key cannot
     * land quietly in a file the sweep never thinks to read.
     */
    public function test_no_field_the_transport_sends_is_undeclared_by_its_constructor(): void
    {
        $this->assertAllDeclared(self::SEND, $this->sendMessageWire(3), 'sendMessage');
        $this->assertAllDeclared(self::SEND, $this->sendMessageWire(null), 'sendMessage without a topic');

        $scope = new FakeUserScope;
        $transport = new UserScopeTransport(
            $scope->resolver(),
            peerResolver: static fn (): array => InputPeer::channel(-1001, '55555'),
            uploader: static fn (string $p, string $m, string $n): array => ['_' => 'inputMediaUploadedDocument', 'mime_type' => $m],
        );

        $transport->call('sendDocument', [
            'chat_id' => -1001,
            'message_thread_id' => 7,
            'document' => '/tmp/x.html',
            'mime' => 'text/html',
            'caption' => 'c',
        ]);

        $this->assertAllDeclared(self::SEND_MEDIA, $scope->onlyParams(), 'sendDocument');
    }

    /**
     * FALSIFICATION 1 — the OLD shape, encoded by the REAL encoder, is
     * accepted, encodes with `flags = 0`, and decodes back with NO `reply_to`
     * at all. No exception. No warning. That is precisely why three green
     * tests missed it, and why `assertAllDeclared()` above is the check that
     * would have failed.
     */
    public function test_the_old_undeclared_shape_encodes_silently_with_no_reply_target(): void
    {
        $old = [
            'peer' => ['_' => 'inputPeerChannel', 'channel_id' => -1001, 'access_hash' => '55555'],
            'message' => '[ALERT] app.alert',
            'random_id' => 42,
            'reply_to_msg_id' => 3,
        ];

        // The encoder is happy. That is the whole problem.
        $bytes = $this->encoded(self::SEND, $old);
        self::assertNotSame('', $bytes, 'the old shape encodes without any complaint');

        $decoded = $this->roundTrip(self::SEND, $old);

        self::assertSame(0, $decoded['flags'], 'flags.0 was never set — the topic is gone');
        self::assertArrayNotHasKey('reply_to', $decoded, 'and reply_to never reaches the wire: the notification lands in General');
        self::assertArrayNotHasKey('reply_to_msg_id', $decoded, 'the undeclared key was dropped whole');

        // And the invariant above rejects it, which is the test that would
        // have gone red on the day. This is the assertion whose failure is
        // the whole point of the file, so it is made explicit rather than
        // left to the reader's imagination.
        $undeclared = array_values(array_diff(array_keys($old), $this->declaredNames(self::SEND), ['flags']));
        self::assertSame(
            ['reply_to_msg_id'],
            $undeclared,
            'this is exactly what the generalised guard reports, and exactly what the three old tests did not',
        );

        try {
            $this->assertAllDeclared(self::SEND, $old, 'the old shape');
            self::fail('assertAllDeclared() must reject the undeclared reply_to_msg_id');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('does not declare', $e->getMessage());
        }
    }

    /**
     * FALSIFICATION 2 — remove the flag bit and the topic assertions go red.
     *
     * A NOTE ON WHAT "REMOVE THE FLAG BIT" CAN MEAN HERE, because getting it
     * wrong would put a false claim in the file. `TLEncoder::encodeObject`
     * does not read a caller-supplied flag word to decide presence: it first
     * seeds the word from `$args['flags']`, then ORs in the bit of every
     * declared conditional field it FINDS in `$args`. So passing
     * `flags => 0` alongside a `reply_to` does not clear the bit — the OR
     * puts it straight back. A "flag set but bit clear" encoding is
     * therefore not reachable from the args array at all; the only way the
     * bit ends up clear is for the field to be ABSENT.
     *
     * So the falsification is done the way it actually happens: drop
     * `reply_to` from the correct wire (what a regression that builds the
     * right object and then loses it looks like) and show the same two
     * assertions — flags bit, `reply_to` on the wire — turn red.
     */
    public function test_dropping_the_reply_target_clears_the_flag_bit_and_fails_the_topic_assertions(): void
    {
        $wire = $this->sendMessageWire(3);
        self::assertArrayHasKey('reply_to', $wire, 'precondition: the real transport does build the reply target');

        $stripped = $wire;
        unset($stripped['reply_to']);

        $decoded = $this->roundTrip(self::SEND, $stripped);

        // The two assertions from test_the_topic_is_on_the_wire…, each
        // NEGATED, and each now demonstrably reachable: the flag bit is
        // clear and `reply_to` is not on the wire. In the topic test those
        // two lines read `assertSame(1, … & 1)` and `assertArrayHasKey`;
        // here they read as below. If either could not go false, that test
        // would be a rumour — this is what makes it evidence.
        self::assertSame(
            0,
            $decoded['flags'] & 1,
            'flags.0 IS clear once the field is absent — so the topic test\'s assertSame(1, … & 1) can fail',
        );
        self::assertArrayNotHasKey(
            'reply_to',
            $decoded,
            'reply_to is NOT on the wire — so the topic test\'s assertArrayHasKey can fail',
        );

        // The constructor id is gone from the bytes too.
        self::assertStringNotContainsString(
            $this->littleEndianHex(TLRegistry::id('inputReplyToMessage')),
            bin2hex($this->encoded(self::SEND, $stripped)),
        );
    }

    /** TL constructor ids travel little-endian, so the wire form is `pack('V', …)`, not `dechex`. */
    private function littleEndianHex(int $id): string
    {
        return bin2hex(pack('V', $id));
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed> the decoded object, read back out of the bytes
     */
    private function roundTrip(string $constructor, array $args): array
    {
        $bytes = $this->encoded($constructor, $args);
        $offset = 0;

        return TLDecoder::decodeObject($bytes, $offset);
    }

    /**
     * The wire params a real `sendMessage` produces, through the real
     * transport, with a stub scope — no socket, no session, no credential.
     *
     * @return array<string, mixed>
     */
    private function sendMessageWire(?int $thread): array
    {
        $scope = new FakeUserScope;
        $transport = new UserScopeTransport(
            $scope->resolver(),
            peerResolver: static fn (): array => InputPeer::channel(-1001, '55555'),
        );

        $params = ['chat_id' => -1001, 'text' => '[ALERT] app.alert'];
        if ($thread !== null) {
            $params['message_thread_id'] = $thread;
        }

        $transport->call('sendMessage', $params);

        return $scope->onlyParams();
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function encoded(string $constructor, array $args): string
    {
        return TLEncoder::encodeObject($constructor, $args);
    }

    /**
     * Every field name a registered constructor declares, read from the live
     * registry rather than copied out of the .tl file.
     *
     * @return list<string>
     */
    private function declaredNames(string $constructor): array
    {
        $names = [];
        foreach (TLRegistry::signatureOf($constructor)->fields as $field) {
            $names[] = $field['name'];
        }

        return $names;
    }

    /**
     * Every key the transport sends must be a declared field of its
     * constructor. `flags` is the one legal extra: the encoder accepts an
     * explicit value for the flag word, so a caller may supply it.
     *
     * @param  array<string, mixed>  $args
     */
    private function assertAllDeclared(string $constructor, array $args, string $label): void
    {
        $undeclared = array_values(array_diff(array_keys($args), $this->declaredNames($constructor), ['flags']));

        self::assertSame(
            [],
            $undeclared,
            sprintf(
                '%s sends field(s) %s that %s does not declare — TLEncoder drops an undeclared key without a word, so the value is silently lost (no exception, no flag bit, no log)',
                $label,
                implode(', ', $undeclared),
                $constructor,
            ),
        );
    }

    /**
     * One declared field of a registered constructor, by name.
     *
     * @return array{name: string, type: string, flagWord: string|null, bit: int|null}|null
     */
    private function field(string $constructor, string $name): ?array
    {
        foreach (TLRegistry::signatureOf($constructor)->fields as $field) {
            if ($field['name'] === $name) {
                return $field;
            }
        }

        return null;
    }
}
