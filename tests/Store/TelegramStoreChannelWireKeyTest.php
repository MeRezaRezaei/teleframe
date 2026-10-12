<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLDecoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLRegistry;
use MeRezaRezaei\Teleframe\Store\MediaReference;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Tests\Support\FakeUserScope;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The store channel's WIRE-KEY contract, checked against the REAL TL registry
 * and the REAL encoder — never against a hardcoded literal.
 *
 * `TLEncoder::encodeObject()` iterates the registry's DECLARED fields and looks
 * each one up in the caller's `$params` BY NAME. An undeclared key is
 * therefore not merely ignored: the declared field is then absent and the
 * encode throws "TLEncoder: missing field '<name>' for <constructor>". This
 * file carries two instances of exactly that, both of which broke a core path
 * unconditionally:
 *
 *  - `uploadPart` sent `total_parts` where upload.saveBigFilePart declares
 *    `file_total_parts` (api.tl:2758) — EVERY putChunk failed, the store
 *    channel's fundamental operation.
 *  - `sendReference` sent `document` + `force_file` where inputMediaDocument
 *    declares `id:InputDocument` (api.tl:37) — EVERY "publish a file Telegram
 *    already holds" failed. Those two keys are the UPLOADED-document shape
 *    (inputMediaUploadedDocument, api.tl:36), copy-pasted from the neighbouring
 *    sendDocument call.
 *
 * Every expectation below is read out of `TLRegistry` at runtime and the params
 * are round-tripped through the real `TLEncoder`/`TLDecoder`. The last three
 * tests are the falsifications: they feed the OLD shapes to the real encoder and
 * require the failure, which is what makes the checks above meaningful rather
 * than tautological.
 */
final class TelegramStoreChannelWireKeyTest extends TestCase
{
    private const BUCKET = 'data:pics';

    private const PART_SIZE = 524288;

    /**
     * Every constructor this file builds, with the site that builds it. The
     * sweep asserts over this list, so adding a call site without auditing it
     * is visible: the count is asserted, not assumed.
     *
     * @return array<string, string> site label => constructor name
     */
    private static function constructions(): array
    {
        return [
            'uploadPart params' => 'upload.saveBigFilePart',
            'sendDocument media' => 'inputMediaUploadedDocument',
            'sendDocument attributes' => 'documentAttributeFilename',
            'putChunk inputFile' => 'inputFileBig',
            'sendReference media' => 'inputMediaDocument',
            'sendReference document' => 'inputDocument',
            'fetchDocument location' => 'inputDocumentFileLocation',
            'downloadDocument location' => 'inputDocumentFileLocation',
            'bucketInputPeer peer' => 'inputPeerChannel',
        ];
    }

    /** @return list<string> declared field names, in declared order, from the real registry */
    private function declaredFields(string $constructor): array
    {
        $names = [];
        foreach (TLRegistry::signatureOf($constructor)->fields as $field) {
            $names[] = $field['name'];
        }

        return $names;
    }

    /**
     * Field names as the VENDORED UPSTREAM SPEC writes them, parsed out of the
     * .tl line itself with string functions only — the repo's zero-regex ethos
     * applied to its tests too. This is the independent source: if the registry
     * and the spec ever disagree, it is the REGISTRY that is wrong and the
     * caller is not the thing to change.
     *
     * @return list<string>
     */
    private function specFieldNames(string $constructor): array
    {
        $tl = (string) file_get_contents(dirname(__DIR__, 2).'/src/Schema/schema/sources/api.tl');
        $prefix = $constructor.'#';

        foreach (explode("\n", $tl) as $line) {
            $line = ltrim($line);
            if (! str_starts_with($line, $prefix)) {
                continue;
            }
            $body = substr($line, (int) strpos($line, ' ') + 1);
            $equals = strpos($body, '=');
            if ($equals !== false) {
                $body = substr($body, 0, $equals);
            }
            $names = [];
            foreach (explode(' ', trim($body)) as $token) {
                if ($token === '') {
                    continue;
                }
                // The name is whatever precedes the first colon; a conditional
                // marker (name:flags.N?Type) keeps the name in the same place.
                $names[] = explode(':', $token)[0];
            }

            return $names;
        }

        self::fail("api.tl no longer declares {$constructor}");
    }

    /**
     * The bare-type union members for a type name (`InputDocument`), read from
     * the spec. A value the registry accepts under a bare type is NOT type
     * checked by the encoder — it dispatches on the nested `_` key alone — so
     * this list is the only thing that catches a wrong nested constructor that
     * still encodes cleanly.
     *
     * @return list<string>
     */
    private function specUnionMembers(string $type): array
    {
        $tl = (string) file_get_contents(dirname(__DIR__, 2).'/src/Schema/schema/sources/api.tl');
        $suffix = '= '.$type.';';
        $members = [];

        foreach (explode("\n", $tl) as $line) {
            $line = trim($line);
            if ($line === '' || ! str_ends_with($line, $suffix)) {
                continue;
            }
            $head = substr($line, 0, -strlen($suffix));
            if (trim($head) === '') {
                continue;
            }
            $name = explode(' ', $head)[0];
            $hash = strpos($name, '#');
            $members[] = $hash === false ? $name : substr($name, 0, $hash);
        }

        return $members;
    }

    /**
     * A raw `document` object as it arrives on the wire — the shape
     * `media.document` carries in message rows.
     *
     * @return array<string, mixed>
     */
    private static function documentRow(): array
    {
        return [
            '_' => 'document',
            'id' => 777,
            'access_hash' => 123456789,
            'file_reference' => 'AQAD-file-reference-bytes',
            'size' => 8,
            'mime_type' => 'application/octet-stream',
        ];
    }

    /**
     * A message row carrying that document, for the search path.
     *
     * @return array<string, mixed>
     */
    private static function searchRow(): array
    {
        return [
            '_' => 'message',
            'id' => 21,
            'message' => 'chunk-0.bin',
            'media' => ['document' => self::documentRow()],
        ];
    }

    /**
     * A recording scope that answers the RPCs the channel needs to get past
     * its own bookkeeping — createChannel (no channel exists yet), the send*
     * calls (the channel pulls the sent message id out of the reply),
     * upload.getFile (an empty part ends the fetch loop) and messages.search
     * (so an entry's fetch() closure, a live call site for downloadDocument,
     * can be invoked). Every call is recorded verbatim for the sweep to read.
     */
    private function recordingScope(): FakeUserScope
    {
        // The anonymous class does not share the test class's scope, so the
        // rows it answers with are handed in at construction.
        return new class(self::searchRow()) extends FakeUserScope
        {
            public function __construct(private readonly array $searchRow)
            {
                parent::__construct();
            }

            private int $nextMessageId = 500;

            public function call(string $method, array $params = []): array
            {
                // Only the RPCs needing a shaped reply are answered here; every
                // other method falls through to the parent, which records the
                // call itself. Recording BOTH would double-count each call.
                $reply = match ($method) {
                    'channels.createChannel' => ['chats' => [[
                        '_' => 'channel',
                        'id' => 4242,
                        'access_hash' => 999,
                        'title' => (string) ($params['title'] ?? ''),
                    ]]],
                    'messages.sendMedia', 'messages.sendMessage' => [
                        'messages' => [['_' => 'message', 'id' => $this->nextMessageId++]],
                    ],
                    // An empty part ends the fetch loop immediately.
                    'upload.getFile' => ['bytes' => ''],
                    'messages.search' => ['messages' => [$this->searchRow]],
                    default => null,
                };

                if ($reply === null) {
                    return parent::call($method, $params);
                }

                $this->calls[] = ['method' => $method, 'params' => $params];

                return $reply;
            }
        };
    }

    /**
     * Drive the REAL channel over its own `scopeApi()` map (which closes over
     * the private static, so this is the production call map, not a hand-built
     * imitation) and return every RPC it made, params exactly as sent.
     *
     * @return list<array{method: string, params: array<string, mixed>}>
     */
    private function driveChannel(): array
    {
        $scope = $this->recordingScope();
        $channel = new TelegramStoreChannel(self::BUCKET, TelegramStoreChannel::scopeApi($scope));

        // putChunk: two parts' worth, so the part-count key must travel on
        // EVERY part and not only on the last.
        $bytes = str_repeat('z', self::PART_SIZE + 1);
        $channel->putChunkBytes('chunk-0.bin', $bytes);

        // putMessage: a text write.
        $channel->putMessage('TFSTORE1 payload');

        // sendReference: the file-Telegram-already-holds publish.
        $channel->sendReference(
            ['_' => 'inputPeerChannel', 'channel_id' => 900, 'access_hash' => 4242],
            MediaReference::fromIndexRow([
                'media_type' => 'document',
                'doc_id' => 777,
                'access_hash' => 123456789,
                'file_reference' => 'AQAD-file-reference-bytes',
                'size' => 4096,
                'mime' => 'image/jpeg',
            ]),
            'holiday.jpg',
        );

        // fetchDocument: the upload.getFile part loop over a raw message row
        // carrying a document, so the instance inputDocumentFileLocation path
        // runs FIRST (the site labels below are positional).
        $channel->fetchDocument([
            'id' => 11,
            'media' => ['document' => self::documentRow()],
        ]);

        // downloadDocument: the static twin, reached through the fetch()
        // closure the search path hands out — a separate construction site with
        // its own literal location array.
        $entries = $channel->findMessagesByName('chunk-0');
        self::assertNotSame([], $entries, 'the search path must yield a fetchable entry');
        // Invoked with ZERO arguments, the way both production call sites
        // (StoreService::fetchChunkPlain, TelegramTier::entryText) invoke it.
        foreach ($entries as $entry) {
            if (is_callable($entry['fetch'] ?? null)) {
                ($entry['fetch'])();
            }
        }

        $channel->deleteMessages([11, 12]);

        return $scope->calls;
    }

    /**
     * Every (site, constructor, params) the file builds, taken off the recorded
     * RPCs, plus the nested values inside them. Returns them in the shape the
     * sweep asserts over.
     *
     * @return array<string, array{0: string, 1: array<string, mixed>}> site => [constructor, params]
     */
    private function capturedConstructions(): array
    {
        $out = [];
        $parts = 0;
        $getFile = 0;

        foreach ($this->driveChannel() as $call) {
            $params = $call['params'];

            if ($call['method'] === 'upload.saveBigFilePart') {
                $parts++;
                $out["uploadPart params #{$parts}"] = ['upload.saveBigFilePart', $params];

                continue;
            }

            if ($call['method'] === 'messages.sendMedia') {
                $media = $params['media'];
                $label = (string) ($media['_'] ?? '?') === 'inputMediaDocument'
                    ? 'sendReference media'
                    : 'sendDocument media';
                $out[$label] = [(string) $media['_'], $media];

                // The uploaded-document path nests an inputFileBig and a
                // Vector<DocumentAttribute> of documentAttributeFilename.
                if (is_array($media['file'] ?? null)) {
                    $out['putChunk inputFile'] = [(string) $media['file']['_'], $media['file']];
                    foreach ((array) ($media['attributes'] ?? []) as $attribute) {
                        $out['sendDocument attributes'] = [(string) $attribute['_'], $attribute];
                    }
                }

                // The reference path nests the InputDocument union member.
                if (is_array($media['id'] ?? null)) {
                    $out['sendReference document'] = [(string) $media['id']['_'], $media['id']];
                }

                // putChunk's own send is addressed to the bucket's peer — the
                // inputPeerChannel literal this file builds. sendReference is
                // given an explicit peer by its caller, so that one is not a
                // construction of this file (it is recorded as a control).
                $out[(string) ($media['_'] ?? '') === 'inputMediaDocument'
                    ? 'caller peer'
                    : 'bucketInputPeer peer'] = ['inputPeerChannel', (array) $params['peer']];

                continue;
            }

            if ($call['method'] === 'upload.getFile') {
                // Two independent construction sites build this same shape;
                // the drive reaches the instance one first, the static one
                // second (via a search entry's fetch() closure).
                $getFile++;
                $out[$getFile === 1 ? 'fetchDocument location' : 'downloadDocument location'] = [
                    'inputDocumentFileLocation',
                    (array) $params['location'],
                ];

                continue;
            }

            if ($call['method'] === 'messages.getHistory' || $call['method'] === 'messages.search') {
                $out['bucketInputPeer peer'] = ['inputPeerChannel', (array) $params['peer']];

                continue;
            }
        }

        return $out;
    }

    /**
     * 1. The premise: TLRegistry is faithful to the vendored spec for EVERY
     *    constructor this file builds. If this fails, the registry is what is
     *    wrong — not the caller.
     */
    public function test_registry_matches_the_vendored_spec_for_every_constructed_type(): void
    {
        $checked = 0;

        foreach (self::constructions() as $site => $constructor) {
            $spec = $this->specFieldNames($constructor);
            self::assertNotSame([], $spec, "{$site}: api.tl declares no fields for {$constructor}");

            // inputPeerChannel is a BARE type (`channel_id:long access_hash:long`)
            // in the spec, so the spec line carries no '#' — the registry is
            // compared on the fields it parsed, not on constructor ids.
            self::assertSame(
                $spec,
                $this->declaredFields($constructor),
                "{$site}: TLRegistry must declare {$constructor} exactly as the vendored spec does"
            );
            $checked++;
        }

        self::assertSame(9, $checked, 'the sweep must cover every construction site in the file');
    }

    /**
     * 2. The two specific declarations the fixes rest on, pinned by NAME
     *    against the spec rather than against this repo's prose.
     */
    public function test_the_two_fixed_declarations_are_what_the_spec_says(): void
    {
        self::assertContains(
            'file_total_parts',
            $this->specFieldNames('upload.saveBigFilePart'),
            'upstream names the big-file part count file_total_parts'
        );
        self::assertNotContains(
            'total_parts',
            $this->specFieldNames('upload.saveBigFilePart'),
            'total_parts is NOT a field of upload.saveBigFilePart at all'
        );

        $media = $this->specFieldNames('inputMediaDocument');
        self::assertContains('id', $media, 'inputMediaDocument declares a non-flag `id`');
        self::assertNotContains('document', $media, 'inputMediaDocument declares no `document` field');
        self::assertNotContains('force_file', $media, 'force_file belongs to inputMediaUploadedDocument, not inputMediaDocument');

        // `id` is non-flag: it must be at a declared position the encoder always
        // writes, not behind a conditional flag bit.
        $flagged = [];
        foreach (TLRegistry::signatureOf('inputMediaDocument')->fields as $field) {
            if ($field['name'] === 'id') {
                $flagged[] = $field['flagWord'];
            }
        }
        self::assertSame([null], $flagged, 'inputMediaDocument.id must be a NON-flag field');
    }

    /**
     * 3. The sweep itself: every captured construction sends ONLY keys the
     *    registry declares, and round-trips through the REAL encoder.
     */
    public function test_every_constructed_payload_encodes_through_the_real_encoder(): void
    {
        $captured = $this->capturedConstructions();
        self::assertNotEmpty($captured, 'the drive must have reached the wire layer');

        $seen = [];
        foreach ($captured as $site => [$constructor, $params]) {
            $declared = $this->declaredFields($constructor);

            // The REAL encoder runs FIRST and on the LIVE construction, so a
            // restored defect fails with the encoder's own throw —
            // "TLEncoder: missing field '<name>' for <constructor>" — rather
            // than with this file's own wording. That ordering is deliberate:
            // it is the difference between evidence and a restatement.
            $decoded = TLDecoder::decodeObject(TLEncoder::encodeObject($constructor, $params));
            self::assertSame($constructor, $decoded['_'], "{$site}: round trip changed the constructor");

            // The encoder silently DROPS an undeclared key, so it cannot catch
            // the extra-key case; the audit below can, and must, run for it.
            foreach (array_keys($params) as $key) {
                if ($key === '_') {
                    continue; // the type tag, not a field
                }
                self::assertContains(
                    $key,
                    $declared,
                    "{$site}: '{$key}' is not a declared field of {$constructor}; "
                    .'TLEncoder would drop it and then throw on the missing declared field'
                );
            }

            // Order proof: the fields that DO reach the wire appear in the
            // registry's declared order, whatever order the caller built its
            // array in. An absent optional flag field is simply not in the
            // sequence, so this is a subsequence check, not equality.
            $cursor = 0;
            foreach (array_keys($decoded) as $name) {
                if ($name === '_') {
                    continue;
                }
                while ($cursor < count($declared) && $declared[$cursor] !== $name) {
                    $cursor++;
                }
                self::assertLessThan(
                    count($declared),
                    $cursor,
                    "{$site}: '{$name}' is on the wire out of declaration order"
                );
                $cursor++;
            }

            $seen[$constructor] = true;
        }

        // Coverage: every site in the declared sweep list was actually reached.
        foreach (array_keys(self::constructions()) as $expected) {
            $hit = false;
            foreach (array_keys($captured) as $site) {
                if (str_starts_with($site, $expected) || $expected === 'read peer' && $site === 'read peer') {
                    $hit = true;
                    break;
                }
            }
            self::assertTrue($hit, "sweep site '{$expected}' was never exercised by the drive");
        }

        self::assertCount(8, $seen, 'eight distinct constructors must be exercised');
    }

    /**
     * 4. W2, proved: the declared part-count key travels on EVERY part, with
     *    the true part count, and the old key never reaches the wire.
     */
    public function test_part_count_reaches_the_wire_at_its_declared_key_on_every_part(): void
    {
        $declared = $this->declaredFields('upload.saveBigFilePart');
        $sent = [];

        foreach ($this->driveChannel() as $call) {
            if ($call['method'] === 'upload.saveBigFilePart') {
                $sent[] = $call['params'];
            }
        }

        // PART_SIZE + 1 bytes over a PART_SIZE planner = two parts.
        self::assertCount(2, $sent, 'the drive must upload more than one part');

        foreach ($sent as $index => $params) {
            $countKey = $declared[2];
            self::assertSame(
                $countKey,
                array_key_exists($countKey, $params) ? $countKey : null,
                "part {$index}: the part count must ride the declared key {$countKey}"
            );

            $decoded = TLDecoder::decodeObject(TLEncoder::encodeObject('upload.saveBigFilePart', $params));
            self::assertSame(2, $decoded[$countKey], "part {$index}: the true part count must reach the wire");
            self::assertSame($params['file_part'], $decoded['file_part']);
            self::assertSame($params['file_id'], $decoded['file_id']);
            self::assertSame($params['bytes'], $decoded['bytes']);
            self::assertArrayNotHasKey('total_parts', $decoded, "part {$index}: the internal key must never reach the wire");
        }
    }

    /**
     * 5. W1, proved: the reference publish sends the declared `id` and a value
     *    that is genuinely a member of the declared InputDocument union.
     *
     *    The union check is the part a key-only test would miss: renaming
     *    `document` to `id` while keeping an inputDocumentFileLocation value
     *    ENCODES CLEANLY (see the falsification below) and would still be
     *    rejected by the server.
     */
    public function test_reference_publish_sends_a_declared_input_document(): void
    {
        $media = null;
        foreach ($this->driveChannel() as $call) {
            if ($call['method'] === 'messages.sendMedia' && ($call['params']['media']['_'] ?? '') === 'inputMediaDocument') {
                $media = $call['params']['media'];
            }
        }
        self::assertNotNull($media, 'the reference publish must reach messages.sendMedia');

        $declared = $this->declaredFields('inputMediaDocument');
        foreach (array_keys($media) as $key) {
            if ($key === '_') {
                continue;
            }
            self::assertContains($key, $declared, "'{$key}' is not declared by inputMediaDocument");
        }
        self::assertArrayHasKey('id', $media, 'the declared non-flag id must be present');
        self::assertArrayNotHasKey('document', $media);
        self::assertArrayNotHasKey('force_file', $media);

        $members = $this->specUnionMembers('InputDocument');
        self::assertContains(
            (string) $media['id']['_'],
            $members,
            'the value nested under id must be a member of the declared InputDocument union; '
            .'inputDocumentFileLocation is an InputFileLocation and belongs to upload.getFile'
        );

        // And the value's own fields must all be declared too.
        $idDeclared = $this->declaredFields((string) $media['id']['_']);
        foreach (array_keys($media['id']) as $key) {
            if ($key === '_') {
                continue;
            }
            self::assertContains($key, $idDeclared, "inputDocument does not declare '{$key}'");
        }

        $decoded = TLDecoder::decodeObject(TLEncoder::encodeObject('inputMediaDocument', $media));
        self::assertSame(777, $decoded['id']['id']);
        self::assertSame(123456789, $decoded['id']['access_hash']);
        self::assertSame('AQAD-file-reference-bytes', $decoded['id']['file_reference']);
    }

    /**
     * 6. The two `total_parts` keys that are NOT the bug. They are internal
     *    ResilientCall params — putChunk hands them to the injected map, and
     *    dispatchResilient reads them back — and must never be "corrected",
     *    because the encoder's declared field is spelled `file_total_parts`
     *    and the internal key is not what the constructor names.
     */
    public function test_the_internal_resilient_part_key_is_distinct_from_the_declared_one(): void
    {
        $declared = $this->declaredFields('upload.saveBigFilePart');

        self::assertNotContains('total_parts', $declared);
        self::assertContains('file_total_parts', $declared);

        // Both internal sites use the same spelling, so the map closure and the
        // reader cannot drift apart. Read straight off the source, since these
        // are positional hand-offs the registry never sees.
        $source = (string) file_get_contents(
            dirname(__DIR__, 2).'/src/Teleframe/Store/TelegramStoreChannel.php'
        );
        self::assertStringContainsString("'total_parts' => \$totalParts,", $source, 'putChunk internal key');
        self::assertStringContainsString("(int) \$params['total_parts'],", $source, 'dispatchResilient internal key');

        // ...and the wire call site uses the declared spelling.
        self::assertStringContainsString("'file_total_parts' => \$totalParts,", $source);
    }

    // ---------------------------------------------------------------- falsify

    /**
     * 7. Falsification A: the OLD `total_parts` shape must fail against the
     *    real registry. Before the fix this is exactly what every putChunk did.
     */
    public function test_the_old_total_parts_shape_is_rejected_by_the_real_encoder(): void
    {
        $before = ['file_id' => 7, 'file_part' => 0, 'total_parts' => 2, 'bytes' => 'abc'];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("TLEncoder: missing field 'file_total_parts' for upload.saveBigFilePart");

        TLEncoder::encodeObject('upload.saveBigFilePart', $before);
    }

    /**
     * 8. Falsification B: the OLD `document`/`force_file` shape must fail
     *    against the real registry. Before the fix this is exactly what every
     *    reference publish did.
     */
    public function test_the_old_document_force_file_shape_is_rejected_by_the_real_encoder(): void
    {
        $before = [
            '_' => 'inputMediaDocument',
            'document' => [
                '_' => 'inputDocumentFileLocation',
                'id' => 777,
                'access_hash' => 123456789,
                'file_reference' => 'AQAD-file-reference-bytes',
                'thumb_size' => '',
            ],
            'force_file' => true,
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("TLEncoder: missing field 'id' for inputMediaDocument");

        TLEncoder::encodeObject('inputMediaDocument', $before);
    }

    /**
     * 9. Falsification C — the one that makes this slice's rule real. Renaming
     *    the key WITHOUT fixing the value type ENCODES CLEANLY. A test that
     *    only checked "no throw, and the key is declared" would have gone green
     *    on a shape Telegram still rejects. This is why the union check in
     *    test_reference_publish_sends_a_declared_input_document is not optional.
     */
    public function test_renaming_the_key_alone_would_encode_and_still_be_wrong(): void
    {
        $renameOnly = [
            '_' => 'inputMediaDocument',
            'id' => [
                '_' => 'inputDocumentFileLocation',
                'id' => 777,
                'access_hash' => 123456789,
                'file_reference' => 'AQAD-file-reference-bytes',
                'thumb_size' => '',
            ],
        ];

        // It encodes. That is the whole point: the encoder does not type-check
        // a bare union, so a green encode proves nothing about correctness.
        $decoded = TLDecoder::decodeObject(TLEncoder::encodeObject('inputMediaDocument', $renameOnly));
        self::assertSame('inputDocumentFileLocation', $decoded['id']['_']);

        // Only the union membership check rejects it.
        self::assertNotContains(
            'inputDocumentFileLocation',
            $this->specUnionMembers('InputDocument'),
            'the encoder accepts it, the spec does not — which is the trap'
        );
    }
}
