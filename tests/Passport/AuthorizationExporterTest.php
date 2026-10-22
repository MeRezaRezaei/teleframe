<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Passport\AuthorizationExporter;
use MeRezaRezaei\Teleframe\Passport\ExportedAuthorization;
use MeRezaRezaei\Teleframe\Passport\MemoryPassportWire;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportWireContract;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayLogger;
use PHPUnit\Framework\TestCase;

/**
 * AuthorizationExporter — `auth.exportAuthorization#e5bfffcd` (OFFICIAL
 * layer-227 id, gap analysis §3.1). The export half of the wire family: it
 * produces the authorization blob the IMPORT half logs in with, so the pair is
 * what makes "let Laravel users log in using it" a round trip rather than a
 * one-way decrypt.
 *
 * The credential rule is the interesting part: the bytes ARE a credential, so
 * `toArray()` carries the id and the byte LENGTH only, and the PSR-3 line
 * carries that same credential-free shape. A test asserts the bytes never
 * reach either.
 */
final class AuthorizationExporterTest extends TestCase
{
    public function test_export_dispatches_with_the_official_method_and_dc_id(): void
    {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_EXPORT_AUTHORIZATION => [
                ['_' => 'auth.exportedAuthorization', 'id' => 9001, 'bytes' => 'opaque-authorization-bytes'],
            ],
        ]);

        $exported = (new AuthorizationExporter($wire))->export(2);

        self::assertInstanceOf(ExportedAuthorization::class, $exported);
        self::assertSame(9001, $exported->id);

        $dispatch = $wire->firstDispatchOf('auth.exportAuthorization');
        self::assertNotNull($dispatch);
        self::assertSame('e5bfffcd', PassportWireContract::OFFICIAL_IDS['auth.exportAuthorization']);
        self::assertSame(['dc_id' => 2], $dispatch['params']);
    }

    public function test_the_dc_id_defaults_to_zero_the_current_dc(): void
    {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_EXPORT_AUTHORIZATION => [
                ['_' => 'auth.exportedAuthorization', 'id' => 1, 'bytes' => 'b'],
            ],
        ]);

        (new AuthorizationExporter($wire))->export();

        self::assertSame(['dc_id' => 0], $wire->firstDispatchOf('auth.exportAuthorization')['params']);
    }

    public function test_the_bytes_never_reach_a_rendered_or_logged_shape(): void
    {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_EXPORT_AUTHORIZATION => [
                ['_' => 'auth.exportedAuthorization', 'id' => 5, 'bytes' => 'super-secret-authorization'],
            ],
        ]);
        $logger = new ArrayLogger;

        $exported = (new AuthorizationExporter($wire, $logger))->export();

        self::assertSame(5, $exported->toArray()['id']);
        self::assertSame(strlen('super-secret-authorization'), $exported->toArray()['bytes_length']);
        self::assertStringNotContainsString('super-secret-authorization', (string) json_encode($exported->toArray()));
        self::assertStringNotContainsString('super-secret-authorization', (string) json_encode($logger->records));
    }

    public function test_a_response_without_an_id_is_refused_loud(): void
    {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_EXPORT_AUTHORIZATION => [
                ['_' => 'auth.exportedAuthorization', 'bytes' => 'b'],
            ],
        ]);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessageMatches('/no usable id/');
        (new AuthorizationExporter($wire))->export();
    }

    public function test_empty_bytes_are_refused_loud(): void
    {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_EXPORT_AUTHORIZATION => [
                ['_' => 'auth.exportedAuthorization', 'id' => 3, 'bytes' => ''],
            ],
        ]);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessageMatches('/empty bytes/');
        (new AuthorizationExporter($wire))->export();
    }
}
