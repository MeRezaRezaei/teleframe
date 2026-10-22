<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLRegistry;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLSignatureParser;
use MeRezaRezaei\Teleframe\Store\MediaReference;
use MeRezaRezaei\Teleframe\Store\MediaType;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;
use PHPUnit\Framework\TestCase;

/**
 * The Cycle 25 landmine: MediaReference's media_type was a free-form string,
 * so a row naming `photo` published as inputMediaDocument — a wire type the
 * server rejects, on a production account, at send time.
 *
 * Every expectation here is read out of the REAL registry (TLRegistry) rather
 * than hardcoded, so the closed set is checked against the wire layer instead
 * of against a copy of it. That is the difference between "photo works" and
 * "photo is the only other thing the wire can construct from a reference".
 */
final class MediaTypeMappingTest extends TestCase
{
    // ---- (a) the closed set is exactly what the wire can construct ----

    public function test_closed_set_is_exactly_the_reference_addressable_input_media(): void
    {
        $addressable = self::referenceAddressableInputMedia();

        self::assertSame(
            ['inputMediaDocument', 'inputMediaPhoto'],
            array_keys($addressable),
            'every InputMedia constructor addressable by a stored reference must have a MediaType',
        );
        self::assertSame(MediaType::names(), ['document', 'photo']);
    }

    public function test_every_media_type_maps_to_a_registered_constructor_pair(): void
    {
        foreach (MediaType::cases() as $case) {
            $outer = $case->inputMediaConstructor();
            $inner = $case->inputIdConstructor();

            self::assertTrue(TLRegistry::isRegistered($outer), "{$outer} must exist on the wire");
            self::assertTrue(TLRegistry::isRegistered($inner), "{$inner} must exist on the wire");
            self::assertSame('InputMedia', TLRegistry::signatureOf($outer)->returnType);
            // TL writes the type as InputDocument; the constructor's own
            // name is inputDocument. Same type, different spelling.
            self::assertSame(
                strtolower($inner),
                strtolower((string) self::fieldType($outer, 'id')),
                "{$outer}.id must be declared {$inner}, not another type",
            );
            // Both value types carry the same triple, which is what makes one
            // builder serve both — and what made a copy-paste between them
            // encode cleanly and still be rejected by the server.
            foreach (['access_hash', 'file_reference'] as $field) {
                self::assertContains($field, self::declaredFields($inner), "{$inner} must declare {$field}");
            }
        }
    }

    public function test_the_two_media_types_are_distinct_on_both_levels(): void
    {
        $outer = [];
        $inner = [];
        foreach (MediaType::cases() as $case) {
            $outer[] = $case->inputMediaConstructor();
            $inner[] = $case->inputIdConstructor();
        }

        self::assertSame($outer, array_unique($outer), 'two media types must not share a constructor');
        self::assertSame($inner, array_unique($inner), 'two media types must not share an id type');
    }

    // ---- (b) the photo/document distinction, on the actual wire shape ----

    public function test_a_photo_reference_publishes_as_the_photo_constructor(): void
    {
        $media = MediaReference::fromIndexRow([
            'media_type' => 'photo',
            'doc_id' => 777,
            'access_hash' => 123456789,
            'file_reference' => 'AQAD-file-reference-bytes',
        ])->toInputMedia();

        self::assertSame('inputMediaPhoto', $media['_']);
        self::assertSame('inputPhoto', $media['id']['_']);

        $document = MediaReference::fromIndexRow([
            'media_type' => 'document',
            'doc_id' => 777,
            'access_hash' => 123456789,
            'file_reference' => 'AQAD-file-reference-bytes',
        ])->toInputMedia();

        self::assertSame('inputMediaDocument', $document['_']);
        self::assertSame('inputDocument', $document['id']['_']);
        self::assertNotSame($media, $document, 'a photo and a document must not publish identically');
    }

    public function test_send_reference_publishes_a_photo_as_input_media_photo(): void
    {
        $api = new FakeStoreApi(scope: 'media-type');
        $channel = new TelegramStoreChannel('data:pics', $api->apiMap());

        $channel->sendReference(
            ['_' => 'inputPeerChannel', 'channel_id' => 900, 'access_hash' => 4242],
            MediaReference::fromIndexRow([
                'media_type' => 'photo',
                'doc_id' => 777,
                'access_hash' => 123456789,
                'file_reference' => 'AQAD-file-reference-bytes',
            ]),
            'holiday.jpg',
        );

        $media = $api->referenceSends[0]['media'];
        self::assertSame('inputMediaPhoto', $media['_']);
        self::assertSame('inputPhoto', $media['id']['_']);
        self::assertSame(0, $api->uploadPartCalls, 'a reference publish still uploads nothing');
    }

    public function test_a_photo_wire_object_builds_a_photo_reference(): void
    {
        $ref = MediaReference::fromDocument([
            '_' => 'photo',
            'id' => 42,
            'access_hash' => 7,
            'file_reference' => 'AQAD-bytes',
        ]);

        self::assertSame(MediaType::Photo, $ref->mediaType());
        self::assertSame('inputMediaPhoto', $ref->toInputMedia()['_']);
    }

    // ---- (c) an unmapped value throws BY NAME, never falls back ----

    public function test_an_unknown_stored_media_type_throws_naming_the_value(): void
    {
        try {
            MediaReference::fromIndexRow([
                'media_type' => 'video',
                'doc_id' => 1,
                'access_hash' => 2,
                'file_reference' => 'ref',
            ]);
            self::fail('a media_type outside the closed set must be refused');
        } catch (InvalidArgumentException $e) {
            self::assertStringContainsString('video', $e->getMessage(), 'the message must name the bad value');
            self::assertStringContainsString('document', $e->getMessage());
            self::assertStringContainsString('photo', $e->getMessage());
        }
    }

    public function test_a_non_media_wire_type_throws_naming_it(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('webpage');
        MediaReference::fromDocument(['_' => 'webpage', 'id' => 5]);
    }

    public function test_an_absent_column_defaults_to_document_but_a_wrong_one_never_does(): void
    {
        $absent = MediaReference::fromIndexRow([
            'doc_id' => 7,
            'access_hash' => 8,
            'file_reference' => 'ref',
        ]);
        self::assertSame(MediaType::Document, $absent->mediaType(), 'an absent column is an old row, not a defect');

        $blank = MediaReference::fromIndexRow([
            'media_type' => '',
            'doc_id' => 7,
            'access_hash' => 8,
            'file_reference' => 'ref',
        ]);
        self::assertSame(MediaType::Document, $blank->mediaType());
    }

    // ---- (d) an existing bad row fails loudly at READ ----

    public function test_a_photo_row_written_by_an_old_build_reads_as_photo_not_document(): void
    {
        // The row a hand-edit or a future writer produces. It must publish as
        // a photo on the first read, with no migration and no coercion.
        $ref = MediaReference::fromIndexRow([
            'media_type' => 'photo',
            'doc_id' => 9001,
            'access_hash' => 55,
            'file_reference' => 'AQAD-existing',
            'size' => 10,
            'mime' => 'image/jpeg',
        ]);

        self::assertSame(MediaType::Photo, $ref->mediaType());
        self::assertSame('inputMediaPhoto', $ref->toInputMedia()['_']);
    }

    // ---- helpers, all read out of the real registry ----

    /**
     * Every InputMedia constructor in the vendored mirror whose `id` field is
     * itself a type carrying access_hash+file_reference — i.e. the ones a
     * stored MediaReference can actually address. Derived, not hardcoded, so
     * a new such constructor added to the schema fails this test.
     *
     * @return array<string, string> inputMedia constructor => its id type
     */
    private static function referenceAddressableInputMedia(): array
    {
        $tripleTypes = self::referenceTripleTypes();
        $found = [];
        foreach (TLRegistry::mirrorLines() as $line) {
            $signature = TLSignatureParser::parse($line);
            if ($signature->returnType !== 'InputMedia') {
                continue;
            }
            // The WHOLE required payload, not merely "has a field of that
            // type": inputMediaDocumentExternal carries an OPTIONAL
            // video_cover:InputPhoto and inputMediaUploadedPhoto a
            // photo:InputFile, so a looser rule would sweep in the external
            // and uploaded constructors, which address nothing and upload
            // bytes. A reference-addressable InputMedia is one whose entire
            // non-flag payload is a single triple-carrying value.
            $required = [];
            foreach ($signature->fields as $field) {
                // '#' is the flags:# pseudo-field, not payload; a flagged
                // field is absent unless its bit is set.
                if ($field['flagWord'] !== null || $field['type'] === '#') {
                    continue;
                }
                $required[] = strtolower(explode('<', $field['type'])[0]);
            }
            if (count($required) === 1 && isset($tripleTypes[$required[0]])) {
                $found[$signature->name] = $required[0];
            }
        }
        ksort($found);

        return $found;
    }

    /**
     * Constructors declaring BOTH access_hash and file_reference — the value
     * shape a MediaReference carries.
     *
     * @return array<string, true>
     */
    private static function referenceTripleTypes(): array
    {
        $types = [];
        foreach (TLRegistry::mirrorLines() as $line) {
            $signature = TLSignatureParser::parse($line);
            $fields = array_column($signature->fields, 'name', 'name');
            if (isset($fields['access_hash'], $fields['file_reference'])) {
                $types[strtolower($signature->name)] = true;
            }
        }

        return $types;
    }

    /** @return list<string> */
    private static function declaredFields(string $constructor): array
    {
        $names = [];
        foreach (TLRegistry::signatureOf($constructor)->fields as $field) {
            $names[] = $field['name'];
        }

        return $names;
    }

    private static function fieldType(string $constructor, string $field): ?string
    {
        foreach (TLRegistry::signatureOf($constructor)->fields as $declared) {
            if ($declared['name'] === $field) {
                return $declared['type'];
            }
        }

        return null;
    }
}
