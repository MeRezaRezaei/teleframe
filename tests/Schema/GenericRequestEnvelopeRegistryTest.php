<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLDecoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLRegistry;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLSerializer;
use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;
use MeRezaRezaei\Teleframe\Schema\WireSchemaCoverage;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * THE 11 GENERIC REQUEST ENVELOPES — what the codec does with a generic
 * declaration, and what it refuses.
 *
 * THE 11 ARE THESE, and this test re-derives the list from the committed
 * sources rather than transcribing it, because a transcribed list is exactly
 * how the 34/36 figures rotted before:
 *
 *   invokeAfterMsg              invokeWithTakeout
 *   invokeAfterMsgs             invokeWithBusinessConnection
 *   initConnection              invokeWithGooglePlayIntegrity
 *   invokeWithLayer             invokeWithApnsSecret
 *   invokeWithoutUpdates        invokeWithReCaptcha
 *   invokeWithMessagesRange
 *
 * Each declares `{X:Type} ... query:!X = X` in `sources/api.tl`. A generic is a
 * schema construct where one declared type (`X`) is a container for another,
 * so the constructor is real and generic-parameterised rather than
 * missing — which is why these are neither the 36 "cannot dial by name" nor
 * the 34 "cannot serialise" the ledger already accounts for.
 *
 * WHAT THIS PINS
 * ==============
 *
 * 1. INERTNESS IS EXPLICIT, NOT ACCIDENTAL. The nine the client does not
 *    hand-build are refused BY NAME at every boundary a host could reach
 *    them through, and the refusal names the constructor. Two of the eleven
 *    (`initConnection`, `invokeWithLayer`) are the wrappers the client
 *    hand-builds on every call; those must keep working.
 *
 * 2. A GENERIC DECLARATION HASHES TO THE ID THE WIRE ACTUALLY USES. This is
 *    the defect `TLRegistry::canonicalize()` closes: registering the BRACED
 *    form `{X:Type}` used to crc32 the raw line, braces included, producing
 *    `0x59729436` for `invokeAfterMsg` where Telegram's id — the one
 *    `sources/api.tl` declares as `invokeAfterMsg#cb9f372d` and the one
 *    `MtprotoServerSession::INVOKE_AFTER_MSG` carries as a hand-held constant —
 *    is `0xcb9f372d`. A wrong id is silent: nothing throws, the envelope just
 *    goes out under a constructor Telegram has never heard of. The fix is
 *    conformance, not design — `crc32Canonical()` has always documented that
 *    "callers must pass already-canonical lines", and `TlCanon` rule 9 has
 *    always said "{ and } removed (generic type args)".
 *
 * 3. THE ONE GENERIC WITH NO CANONICAL STRING IS REFUSED BY NAME. The bare
 *    container `vector {t:Type} # [ t ] = Vector t` carries `[ t ]`, a
 *    schema-level pseudo-field in no canonical string, so its id cannot be
 *    derived — only guessed. The codec addresses that container by its fixed
 *    id (`TLRegistry::VECTOR`) instead, which is what the round-trip below
 *    proves still works.
 *
 * NOTHING HERE REGISTERS AN ENVELOPE. Doing so would move `isRegistered()`,
 * and with it `WireSchemaCoverage::clientBuilt()` / `isEncodable()` and the
 * 34/36 boundary — a registry design decision that belongs to a later cycle,
 * recorded in `docs/verbatim-goals.md`. Every assertion below is on pure
 * functions or on constructors the client already registered, so this file
 * leaves `TLRegistry`'s static state byte-for-byte as it found it.
 */
final class GenericRequestEnvelopeRegistryTest extends TestCase
{
    /**
     * The 11 envelope constructor names, in the order `sources/api.tl`
     * declares them. Transcribed names are fine HERE on purpose: the set is
     * cross-checked against the sources by {@see testTheElevenAreEveryGenericWrapperInTheCatalogue()},
     * so a new envelope fails that test rather than passing unnoticed.
     *
     * @var list<string>
     */
    private const ENVELOPES = [
        'invokeAfterMsg',
        'invokeAfterMsgs',
        'initConnection',
        'invokeWithLayer',
        'invokeWithoutUpdates',
        'invokeWithMessagesRange',
        'invokeWithTakeout',
        'invokeWithBusinessConnection',
        'invokeWithGooglePlayIntegrity',
        'invokeWithApnsSecret',
        'invokeWithReCaptcha',
    ];

    /**
     * The two envelopes the client hand-builds on every call, and therefore the
     * two that must keep serialising. `EncryptedConnection::buildFirstQueryBody()`
     * frames the first one by hand; every later call goes through the second.
     *
     * @var list<string>
     */
    private const CLIENT_BUILT = ['initConnection', 'invokeWithLayer'];

    /** The nine: real constructors, but nothing in this repo constructs them. */
    private const INERT = [
        'invokeAfterMsg',
        'invokeAfterMsgs',
        'invokeWithoutUpdates',
        'invokeWithMessagesRange',
        'invokeWithTakeout',
        'invokeWithBusinessConnection',
        'invokeWithGooglePlayIntegrity',
        'invokeWithApnsSecret',
        'invokeWithReCaptcha',
    ];

    // ------------------------------------------------------------------
    // (a) each now-refused generic throws BY NAME
    // ------------------------------------------------------------------

    public function test_the_bare_generic_container_is_refused_by_name(): void
    {
        // Verbatim from sources/api.tl:6 and sources/mtproto_full.tl:3.
        $line = 'vector#1cb5c415 {t:Type} # [ t ] = Vector t';

        try {
            TLRegistry::register($line);
            self::fail('register() accepted a generic whose id cannot be derived: '.$line);
        } catch (InvalidArgumentException $e) {
            // By NAME, twice over: the constructor, and the type that was refused.
            self::assertStringContainsString(
                "generic 'vector' cannot be registered",
                $e->getMessage(),
                'the refusal must name the generic it refused',
            );
            self::assertStringContainsString(
                '0x1cb5c415',
                $e->getMessage(),
                'the refusal must point at the fixed id the codec uses instead',
            );
        }

        // And the refusal must leave no residue: a refused generic is not
        // half-registered, or the next lookup would answer for a constructor
        // the codec just declared unregistrable.
        self::assertFalse(
            TLRegistry::isRegistered('vector'),
            'a refused generic must not be left in the registry',
        );
    }

    public function test_every_inert_envelope_is_refused_by_name_at_the_encoder(): void
    {
        foreach (self::INERT as $name) {
            try {
                TLEncoder::encodeObject($name, ['query' => ['_' => 'help.getNearestDc']]);
                self::fail("{$name} encoded; it declares a generic nothing in this repo constructs");
            } catch (InvalidArgumentException $e) {
                self::assertStringContainsString(
                    "unknown constructor '{$name}'",
                    $e->getMessage(),
                    "the refusal for {$name} must name it",
                );
            }
        }
    }

    public function test_every_inert_envelope_is_refused_by_name_at_the_registry(): void
    {
        foreach (self::INERT as $name) {
            self::assertFalse(TLRegistry::isRegistered($name), "{$name} must not be registered");
            self::assertFalse(
                WireSchemaCoverage::clientBuilt($name),
                "{$name} is inert: nothing in this repo hands it to the encoder",
            );

            $reason = WireSchemaCoverage::envelopeReason($name);
            self::assertNotNull($reason, "{$name} must carry an envelope reason");
            self::assertStringContainsString($name, $reason, "the envelope reason for {$name} must name it");
        }
    }

    /**
     * A generic envelope is not a method a host may dial, and the reason it
     * is not must say THAT — not "we forgot to register this", which is what
     * a bare unknown-constructor word implies.
     */
    public function test_every_envelope_carries_an_envelope_reason_not_a_silent_skip(): void
    {
        foreach (self::ENVELOPES as $name) {
            self::assertTrue(WireSchemaCoverage::isEnvelope($name), "{$name} must classify as an envelope");

            $reason = WireSchemaCoverage::envelopeReason($name);
            self::assertNotNull($reason);
            self::assertStringContainsString('ENVELOPE', $reason);
            self::assertStringContainsString('{X:Type}', $reason, "{$name}'s reason must quote the shape it read");
        }
    }

    // ------------------------------------------------------------------
    // (b) the genuinely supported subset still constructs correctly
    // ------------------------------------------------------------------

    /**
     * The two client-built envelopes must keep round-tripping THROUGH the
     * generic declaration — the declaration is parsed away (it is not a wire
     * field) and the bound `!X` use still serialises as a nested object.
     *
     * `invokeWithLayer` here is exactly what `EncryptedConnection` frames on
     * every call after the first, so a regression here is a regression on the
     * live wire path, not on a test fixture.
     */
    public function test_client_built_envelope_still_serialises_through_its_generic_declaration(): void
    {
        $body = TLEncoder::encodeObject('invokeWithLayer', [
            'layer' => 227,
            'query' => ['_' => 'help.getNearestDc'],
        ]);

        // Constructor id first, then layer:int, then the wrapped request.
        self::assertSame(
            pack('V', TLRegistry::id('invokeWithLayer')).pack('V', 227).pack('V', TLRegistry::id('help.getNearestDc')),
            $body,
            'invokeWithLayer must frame as id || layer:int || wrapped request',
        );

        $offset = 0;
        $decoded = TLDecoder::decodeObject($body, $offset);
        self::assertSame('invokeWithLayer', $decoded['_']);
        self::assertSame(227, $decoded['layer']);
        self::assertSame(
            ['_' => 'help.getNearestDc'],
            $decoded['query'],
            'the bound !X variable must decode as the concrete request the host supplied',
        );
    }

    /**
     * `initConnection` is the other client-built wrapper, and the one with a
     * conditional generic-adjacent field (`params:flags.1?JSONValue`). It is
     * built by `EncryptedConnection::buildFirstQueryBody()`; the assertion here
     * is that the generic declaration did not become a wire field and the
     * conditional stayed conditional.
     */
    public function test_init_connection_generic_declaration_is_not_a_field_and_flags_stay_conditional(): void
    {
        $fields = [];
        foreach (TLRegistry::signatureOf('initConnection')->fields as $field) {
            $fields[$field['name']] = $field;
        }

        self::assertArrayNotHasKey(
            'X',
            $fields,
            'the {X:Type} declaration is a schema binding, never a wire field',
        );
        self::assertArrayHasKey('query', $fields);
        self::assertSame('X', $fields['query']['type'], 'the bound !X use normalizes to the variable name');
        self::assertSame('flags', $fields['params']['flagWord']);
        self::assertSame(1, $fields['params']['bit']);

        $body = TLEncoder::encodeObject('initConnection', [
            'flags' => 2,
            'api_id' => 1,
            'device_model' => 'php',
            'system_version' => 'php',
            'app_version' => '1.0',
            'system_lang_code' => 'en',
            'lang_pack' => '',
            'lang_code' => 'en',
            'params' => ['_' => 'jsonNull'],
            'query' => ['_' => 'help.getNearestDc'],
        ]);
        $offset = 0;
        $decoded = TLDecoder::decodeObject($body, $offset);
        self::assertSame('initConnection', $decoded['_']);
        self::assertSame(2, $decoded['flags']);
        self::assertSame(
            ['_' => 'jsonNull'],
            $decoded['params'],
            'JSONValue is a sum type the registry holds concretely, so the conditional field '
            .'is encodable, not a placeholder',
        );
        self::assertSame(['_' => 'help.getNearestDc'], $decoded['query']);
    }

    /**
     * The bare generic container is addressed by its FIXED id, not by a
     * signature — that is the reason refusing to register it is safe. This is
     * the "supported subset" for `vector`: any element type, because the
     * element is self-describing on the wire.
     */
    public function test_bare_generic_container_still_round_trips_by_its_fixed_id(): void
    {
        $packed = TLSerializer::packVector(
            [1, 2, 3],
            static fn (int $item): string => TLSerializer::packLong($item),
        );
        self::assertSame(pack('V', TLRegistry::VECTOR), substr($packed, 0, 4), 'vector opens with its fixed id');

        $offset = 0;
        self::assertSame([1, 2, 3], TLSerializer::unpackVector(
            $packed,
            $offset,
            static fn (string $d, int &$o): mixed => TLSerializer::unpackLong($d, $o),
        ));
    }

    // ------------------------------------------------------------------
    // (c) the derivation the counts depend on
    // ------------------------------------------------------------------

    /**
     * The 11 are every `{X:Type}` function line in the committed catalogue
     * source. If a schema update adds or removes an envelope, this fails
     * rather than leaving the constant quietly wrong.
     */
    public function test_the_eleven_are_every_generic_wrapper_in_the_catalogue(): void
    {
        $found = [];
        $inFunctions = false;
        $path = SchemaArtifacts::source('api.tl');
        self::assertFileExists($path);

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $raw) {
            $line = trim($raw);
            if ($line === '---functions---') {
                $inFunctions = true;

                continue;
            }
            if ($line === '---types---') {
                $inFunctions = false;

                continue;
            }
            if (! $inFunctions || $line === '' || $line[0] === '/' || str_starts_with($line, '---')) {
                continue;
            }
            if (! str_contains($line, '{X:Type}')) {
                continue;
            }
            $token = explode(' ', $line)[0];
            $hash = strpos($token, '#');
            $found[] = $hash === false ? $token : substr($token, 0, $hash);
        }

        sort($found);
        $expected = self::ENVELOPES;
        sort($expected);
        self::assertSame($expected, $found, 'the generic-wrapper set moved; update the constant and the ledger');
    }

    // ------------------------------------------------------------------
    // the defect itself: a generic declaration must hash to the real id
    // ------------------------------------------------------------------

    /**
     * THE FALSIFIABLE PROPERTY. A braced generic declaration and its
     * brace-stripped form are the SAME constructor, so they must produce the
     * SAME crc32 — and that crc32 must be the id Telegram actually assigned.
     *
     * The oracle is not this test's memory: `sources/api.tl` declares
     * `invokeAfterMsg#cb9f372d`, and `MtprotoServerSession` holds the same
     * number as `INVOKE_AFTER_MSG` precisely because TLRegistry could not.
     */
    public function test_braced_and_bare_generic_forms_hash_to_the_same_real_id(): void
    {
        $braced = 'invokeAfterMsg {X:Type} msg_id:long query:!X = X';
        $bare = 'invokeAfterMsg X:Type msg_id:long query:!X = X';

        self::assertSame($bare, TLRegistry::canonicalize($braced), 'canonicalize() must fold the braces away');
        self::assertSame(
            TLRegistry::crc32Canonical($bare),
            TLRegistry::crc32Canonical(TLRegistry::canonicalize($braced)),
            'the braced form must not hash to its own id',
        );

        // 0xcb9f372d — what sources/api.tl declares. The braced form used to
        // yield 0x59729436 here, silently.
        self::assertSame(0xCB9F372D, TLRegistry::crc32Canonical(TLRegistry::canonicalize($braced)));

        // And the id committed in the source is the same number, read from the
        // file rather than remembered.
        $declared = null;
        foreach (file(SchemaArtifacts::source('api.tl'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $raw) {
            if (str_contains($raw, 'invokeAfterMsg#')) {
                $declared = trim($raw);
                break;
            }
        }
        self::assertNotNull($declared, 'the committed source no longer declares invokeAfterMsg');
        self::assertStringStartsWith('invokeAfterMsg#cb9f372d', $declared, 'the committed id moved; re-derive it');
    }

    /**
     * The CALL SITE, not just the pure function: `register()` must hash the
     * canonicalized line, so registering the braced form of a constructor the
     * registry already holds cannot move its id.
     *
     * Uses `invokeWithLayer` — the one generic the client hand-builds on every
     * single call, already registered from its brace-less `SCHEMA` line — and
     * restores that exact line afterwards, so the registry's static state is
     * byte-for-byte what it was on entry. Falsifying this test means
     * re-registering from the raw braced line, which would quietly repoint a
     * live constructor id.
     */
    public function test_registering_the_braced_form_does_not_move_a_live_constructor_id(): void
    {
        $braced = 'invokeWithLayer {X:Type} layer:int query:!X = X';
        $original = TLRegistry::signature('invokeWithLayer');
        self::assertSame(
            'invokeWithLayer X:Type layer:int query:!X = X',
            $original,
            'the hand-built SCHEMA line moved; re-derive the paired form',
        );

        $before = TLRegistry::id('invokeWithLayer');
        try {
            TLRegistry::register($braced);
            self::assertSame(
                $before,
                TLRegistry::id('invokeWithLayer'),
                'register() hashed the raw braced line, so the id moved off the wire',
            );
            self::assertSame(0xDA9B0D0D, $before, 'the live id is not the one sources/api.tl declares');
        } finally {
            TLRegistry::register($original);
        }

        self::assertSame($before, TLRegistry::id('invokeWithLayer'), 'the id was not restored');
        self::assertSame($original, TLRegistry::signature('invokeWithLayer'), 'the signature was not restored');
    }

    /**
     * Canonicalizing is a pure function and must not disturb any other line:
     * a constructor with no brace in it is returned byte-identical.
     */
    public function test_canonicalize_leaves_non_generic_lines_byte_identical(): void
    {
        $line = 'msgs_ack msg_ids:Vector long = MsgsAck';
        self::assertSame($line, TLRegistry::canonicalize($line));

        $id = 'inputPeerUser#dde8a54c user_id:long access_hash:long = InputPeer';
        self::assertSame($id, TLRegistry::canonicalize($id));
    }

    /**
     * A generic field is not a wildcard: the value must still be a real,
     * registered constructor. A scalar, or a nested array with no `_`, must be
     * refused — and named.
     */
    public function test_a_generic_field_refuses_a_value_that_is_not_a_constructor(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot encode X from scalar');
        TLEncoder::encodeValue('X', 'help.getNearestDc');
    }

    public function test_a_generic_field_refuses_an_array_with_no_constructor_key(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('nested object missing "_" key');
        TLEncoder::encodeValue('X', ['not-a-constructor' => 1]);
    }
}
