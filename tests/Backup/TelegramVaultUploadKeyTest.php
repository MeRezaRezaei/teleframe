<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use MeRezaRezaei\Teleframe\Backup\TelegramVault;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLDecoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLRegistry;
use MeRezaRezaei\Teleframe\Tests\Support\FakeUserScope;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The backup uploader's wire-key contract, checked against the REAL TL
 * registry rather than a hardcoded expectation.
 *
 * `TelegramVault::uploadBytes()` drives `upload.saveBigFilePart` for payloads
 * over BIG_FILE_THRESHOLD. `TLEncoder::encodeObject()` iterates the REGISTRY's
 * declared fields and looks each one up in the caller's `$params` BY NAME, so
 * a key the schema does not declare is not merely ignored: the real declared
 * field is then absent and the encode throws
 * "TLEncoder: missing field '<name>' for upload.saveBigFilePart". This vault
 * used to send `total_parts` where the constructor declares `file_total_parts`
 * — every backup over 10 MB failed at the first part.
 *
 * Nothing here asserts a literal against a literal: every expectation is read
 * out of `TLRegistry` at runtime and the params are round-tripped through the
 * real `TLEncoder`/`TLDecoder`. The final test is the falsification — it feeds
 * the OLD key to the real encoder and requires the failure, proving the checks
 * above would have caught the bug rather than merely describing the fix.
 */
final class TelegramVaultUploadKeyTest extends TestCase
{
    private const PART_SIZE = 524288;

    private const BIG_FILE_THRESHOLD = 10485760;

    // vendor/ is a symlink to the main tree: pin this tree's (fixed)
    // TelegramVault sources before any test body autoloads them.
    public static function setUpBeforeClass(): void
    {
        require dirname(__DIR__, 2).'/tests/Schema/FixesAutoload.php';
    }

    /** @return list<string> declared field names, in declared order, from the real registry */
    private function declaredFields(string $method): array
    {
        $names = [];
        foreach (TLRegistry::signatureOf($method)->fields as $field) {
            $names[] = $field['name'];
        }

        return $names;
    }

    /**
     * Drive the REAL vault uploader (via its own scopeApi map, which closes
     * over the private static) and return every `upload.saveBigFilePart` call
     * it made, params as sent.
     *
     * @return list<array<string, mixed>>
     */
    private function bigFileUploadParams(): array
    {
        $scope = new FakeUserScope;

        // BIG_FILE_THRESHOLD + 1 byte: one byte over the switch, so the big
        // path is taken, and the part count is the smallest that proves the
        // part-count key travels on EVERY part rather than just the last.
        $bytes = str_repeat('x', self::BIG_FILE_THRESHOLD + 1);
        $expectedParts = (int) ceil(strlen($bytes) / self::PART_SIZE);

        $inputFile = TelegramVault::scopeApi($scope)['uploadBytes']('chunk.bin', $bytes);

        $sent = [];
        foreach ($scope->calls as $call) {
            if ($call['method'] === 'upload.saveBigFilePart') {
                $sent[] = $call['params'];
            }
        }

        self::assertCount($expectedParts, $sent, 'every part must ride upload.saveBigFilePart');
        self::assertSame($expectedParts, $inputFile['parts']);

        return $sent;
    }

    public function test_registry_declares_the_key_the_upstream_spec_declares(): void
    {
        // The premise of the fix, pinned against the vendored upstream spec
        // rather than against this repo's own prose: api.tl is the file the
        // schema pipeline is generated from.
        $line = self::tlDeclaration('upload.saveBigFilePart#de7b673d');
        self::assertNotSame('', $line, 'api.tl must still declare upload.saveBigFilePart#de7b673d');

        // Field names in spec order, taken from the .tl line itself. String
        // functions only — the repo's zero-regex ethos applied to its tests too.
        $space = strpos($line, ' ');
        self::assertNotFalse($space);
        $specNames = self::fieldNamesOf(substr($line, $space));

        // The registry must be faithful to the spec. If this fails, the REGISTRY
        // is what is wrong — not the caller — and the fix belongs elsewhere.
        self::assertSame(
            $specNames,
            $this->declaredFields('upload.saveBigFilePart'),
            'TLRegistry must declare upload.saveBigFilePart exactly as the vendored spec does'
        );
        self::assertContains(
            'file_total_parts',
            $specNames,
            'upstream names the big-file part count file_total_parts'
        );
    }

    public function test_uploader_sends_only_keys_the_registry_declares(): void
    {
        $declared = $this->declaredFields('upload.saveBigFilePart');

        foreach ($this->bigFileUploadParams() as $index => $params) {
            foreach (array_keys($params) as $key) {
                self::assertContains(
                    $key,
                    $declared,
                    "part {$index}: '{$key}' is not a declared field of upload.saveBigFilePart; "
                    .'TLEncoder would drop it and then throw on the missing declared field'
                );
            }
            // Nothing missing either: every declared field of this constructor
            // is required (no flag words), so a short array also throws.
            self::assertSame(
                $declared,
                array_values(array_intersect($declared, array_keys($params))),
                "part {$index}: declared fields absent from the params as sent"
            );
        }
    }

    public function test_part_count_reaches_the_wire_at_its_declared_position(): void
    {
        $declared = $this->declaredFields('upload.saveBigFilePart');
        $sent = $this->bigFileUploadParams();
        $expectedParts = count($sent);

        foreach ($sent as $index => $params) {
            // The real encoder, driven by the real registry. Before the fix
            // this threw: "missing field 'file_total_parts'".
            $wire = TLEncoder::encodeObject('upload.saveBigFilePart', $params);

            $decoded = TLDecoder::decodeObject($wire);
            self::assertSame('upload.saveBigFilePart', $decoded['_'], "part {$index}: the encoder must emit this constructor");

            // Order proof: the decoded key sequence is the registry's declared
            // order, so the part count is packed at the declared slot whatever
            // order the caller happened to build its array in.
            $wireOrder = array_values(array_diff(array_keys($decoded), ['_']));
            self::assertSame($declared, $wireOrder, "part {$index}: wire field order must follow the declaration");

            // Value proof: the declared int carries the true part count.
            $countKey = $declared[2];
            self::assertSame($expectedParts, $decoded[$countKey], "part {$index}: part count must reach the wire");
            self::assertSame($params['file_part'], $decoded['file_part'], "part {$index}: file_part must reach the wire unchanged");
            self::assertSame($params['file_id'], $decoded['file_id'], "part {$index}: file_id must reach the wire unchanged");
            self::assertSame($params['bytes'], $decoded['bytes'], "part {$index}: bytes must reach the wire unchanged");
        }
    }

    public function test_small_payload_uses_save_file_part_and_declares_no_part_count(): void
    {
        $declared = $this->declaredFields('upload.saveFilePart');
        $scope = new FakeUserScope;
        $bytes = str_repeat('y', 1024);

        TelegramVault::scopeApi($scope)['uploadBytes']('small.bin', $bytes);

        $calls = [];
        foreach ($scope->calls as $call) {
            $calls[] = $call;
        }
        self::assertNotEmpty($calls);
        foreach ($calls as $call) {
            self::assertSame('upload.saveFilePart', $call['method'], 'a small payload must take the saveFilePart path');
            foreach (array_keys($call['params']) as $key) {
                self::assertContains($key, $declared, "'{$key}' is not declared by upload.saveFilePart");
            }
            // saveFilePart has no part-count field at all; the big-file key
            // must not leak onto the small-file path.
            self::assertArrayNotHasKey('file_total_parts', $call['params'], 'saveFilePart declares no part count, so the big-file key must not leak onto it');
        }
    }

    /**
     * The one declaration line in the vendored `api.tl` that opens with
     * $signature — the file the schema pipeline is generated from. Named, so
     * the test says which declaration it is reading; '' when api.tl no longer
     * declares it, which the caller asserts on.
     */
    private static function tlDeclaration(string $signature): string
    {
        $tl = (string) file_get_contents(
            dirname(__DIR__, 2).'/src/Schema/schema/sources/api.tl'
        );

        foreach (explode("\n", $tl) as $candidate) {
            if (str_starts_with(ltrim($candidate), $signature)) {
                return trim($candidate);
            }
        }

        return '';
    }

    /**
     * The declared field names of a `.tl` declaration body, in spec order,
     * with the `= type` result annotation stripped. String functions only —
     * the repo's zero-regex ethos applied to its tests too.
     *
     * @return list<string>
     */
    private static function fieldNamesOf(string $body): array
    {
        $equals = strpos($body, '=');
        if ($equals !== false) {
            $body = substr($body, 0, $equals);
        }

        $names = [];
        foreach (explode(' ', trim($body)) as $token) {
            if ($token === '') {
                continue;
            }
            $names[] = explode(':', $token)[0];
        }

        return $names;
    }

    /**
     * Falsification. Sending the OLD key must fail against the real registry —
     * this is what makes the checks above meaningful rather than tautological.
     */
    public function test_the_old_total_parts_key_is_rejected_by_the_real_encoder(): void
    {
        $before = ['file_id' => 7, 'file_part' => 0, 'bytes' => 'abc', 'total_parts' => 2];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("TLEncoder: missing field 'file_total_parts' for upload.saveBigFilePart");

        TLEncoder::encodeObject('upload.saveBigFilePart', $before);
    }

    /** The fixed key encodes cleanly where the old one threw — same registry, both sides. */
    public function test_the_declared_key_encodes_where_the_old_one_threw(): void
    {
        $after = ['file_id' => 7, 'file_part' => 0, 'bytes' => 'abc', 'file_total_parts' => 2];

        $decoded = TLDecoder::decodeObject(TLEncoder::encodeObject('upload.saveBigFilePart', $after));

        self::assertSame(2, $decoded['file_total_parts']);
        self::assertArrayNotHasKey('total_parts', $decoded, 'the old key never reaches the wire');
    }
}
