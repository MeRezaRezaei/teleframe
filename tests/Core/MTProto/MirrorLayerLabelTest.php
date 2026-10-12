<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\MTProto;

use MeRezaRezaei\Teleframe\Core\MTProto\Connection\EncryptedConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLRegistry;
use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;
use PHPUnit\Framework\TestCase;

/**
 * The anti-rot guard for the layer mislabel.
 *
 * TLRegistry used to describe `src/Schema/schema/sources/api_full.tl` as a
 * "Full v227 mirror". It is not: that file carries layer-229 constructors
 * (`messages.sendMessage#fef48f62` with `suggested_post:flags.22?` and
 * `rich_message:flags.23?`), and so does the packaged catalogue. The wire, by
 * contrast, advertises 227. The integer gap is deliberate; the LABEL was the
 * defect, and no test would have noticed.
 *
 * This class is the mechanism that would have noticed: a constructor id is a
 * CRC32 of the canonical signature, so if the mirror and the catalogue are
 * from different layers, their ids for the same constructor disagree. The
 * check is one file pass plus one JSON decode, so it is cheap enough to sit in
 * the normal suite rather than in a release checklist.
 *
 * It is a HARD FAILURE for any divergence that is not named in
 * TLRegistry::KNOWN_MIRROR_DIVERGENCES, and equally a hard failure for a named
 * exemption that no longer applies. A guard that only warns is a guard nobody
 * reads, and a warning that never fires is the bug it was written to catch.
 */
final class MirrorLayerLabelTest extends TestCase
{
    public function test_the_catalogue_layer_is_read_from_the_manifest_not_guessed(): void
    {
        $manifest = json_decode(
            (string) file_get_contents(SchemaArtifacts::path('schema-manifest.json')),
            true,
        );

        self::assertIsArray($manifest);
        self::assertArrayHasKey('layer', $manifest);
        self::assertSame($manifest['layer'], TLRegistry::catalogueLayer());
    }

    public function test_the_catalogue_layer_is_not_the_wire_layer(): void
    {
        // Stated so the intent is checkable: the two numbers are SUPPOSED to
        // differ. If a future change makes them equal, that is a change of
        // intent, not a bug fix, and it should fail here rather than pass
        // quietly in either direction.
        self::assertNotSame(
            EncryptedConnection::LAYER,
            TLRegistry::catalogueLayer(),
            'wire and catalogue layers are expected to differ; equality means the deliberate gap was closed somewhere',
        );
    }

    public function test_the_mirror_is_layer_catalogue_content_and_not_the_wire_layer(): void
    {
        // The direct evidence for the mislabel: a field that exists only in
        // layer 229, read out of the file the old comment called "v227".
        $lines = TLRegistry::mirrorLines();
        $sendMessage = null;
        foreach ($lines as $line) {
            if (str_starts_with($line, 'messages.sendMessage#')) {
                $sendMessage = $line;
                break;
            }
        }

        self::assertIsString($sendMessage, 'messages.sendMessage must be present in the vendored mirror');
        self::assertStringContainsString('suggested_post:flags.22?SuggestedPost', $sendMessage);
        self::assertStringContainsString('rich_message:flags.23?InputRichMessage', $sendMessage);
        self::assertStringContainsString('#fef48f62', $sendMessage, 'the layer-229 id of messages.sendMessage');
    }

    public function test_mirror_ids_agree_with_the_catalogue_unless_named(): void
    {
        $report = TLRegistry::mirrorIdDivergenceReport();

        self::assertSame(
            [],
            $report['unexpected'],
            'a mirror constructor id disagrees with the schema catalogue and is not named in TLRegistry::KNOWN_MIRROR_DIVERGENCES',
        );
    }

    public function test_every_named_divergence_is_still_real(): void
    {
        $report = TLRegistry::mirrorIdDivergenceReport();

        self::assertSame(
            [],
            $report['stale'],
            'TLRegistry::KNOWN_MIRROR_DIVERGENCES names a divergence that no longer exists; delete the exemption',
        );
    }

    public function test_the_guard_can_actually_return_a_difference(): void
    {
        // Falsify the guard itself. If idsAgree() could not tell two ids apart,
        // then "no unexpected divergences" above would be true for the wrong
        // reason — a comparison that silently never fires, which is the exact
        // failure mode this guard exists to prevent.
        self::assertTrue(TLRegistry::idsAgree('fef48f62', '0xfef48f62'), 'formatting differences are not differences');
        self::assertTrue(TLRegistry::idsAgree('FEF48F62', 'fef48f62'), 'case is not a difference');
        self::assertFalse(
            TLRegistry::idsAgree('4bc6589a', '6126a43c'),
            'a real id disagreement MUST be reportable, or this guard certifies nothing',
        );
    }

    public function test_the_guard_covers_a_meaningful_number_of_constructors(): void
    {
        $catalogue = json_decode(
            (string) file_get_contents(SchemaArtifacts::path('methods-mtproto.json')),
            true,
        );

        self::assertIsArray($catalogue);
        self::assertIsArray($catalogue['methods']);
        self::assertGreaterThan(
            500,
            count($catalogue['methods']),
            'the guard is only meaningful over a real slice of the catalogue',
        );
    }
}
