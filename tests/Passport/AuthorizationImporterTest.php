<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\RpcErrorException;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\Schema\UserScopeSchema;
use MeRezaRezaei\Teleframe\Passport\AuthorizationImporter;
use MeRezaRezaei\Teleframe\Passport\ImportedAuthorization;
use MeRezaRezaei\Teleframe\Passport\MemoryPassportWire;
use MeRezaRezaei\Teleframe\Passport\PassportFieldError;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportRefusal;
use MeRezaRezaei\Teleframe\Passport\PassportWireContract;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * The passport WIRE family (Cycle 18) — the part of the 2026-09-24 gap
 * analysis that was ruled WRONG. These tests pin three things that must not
 * silently regress:
 *
 *  1. `auth.importAuthorization` really is dispatchable, and dispatches with
 *     the OFFICIAL layer-227 constructor id `#a57a7dad` and exactly the two
 *     documented params — the id is cross-checked against the generated wire
 *     closure (UserScopeSchema::LINES), so a seed regression is loud here
 *     rather than discovered as an encoder failure on a live wire.
 *  2. A `PASSWORD_REQUIRED` refusal survives the importer AS ITSELF (it must
 *     stay classifiable), because the whole 2FA route hangs off it.
 *  3. Everything unusable — no id, empty bytes, a sign-up demand, a user with
 *     no id — is refused LOUD, never a half-imported authorization.
 */
final class AuthorizationImporterTest extends TestCase
{
    public function test_official_ids_are_the_layer_227_constructor_ids(): void
    {
        self::assertSame('a57a7dad', PassportWireContract::OFFICIAL_IDS[PassportWireContract::METHOD_IMPORT_AUTHORIZATION]);
        self::assertSame('e5bfffcd', PassportWireContract::OFFICIAL_IDS[PassportWireContract::METHOD_EXPORT_AUTHORIZATION]);
        self::assertSame('c661ad08', PassportWireContract::OFFICIAL_IDS[PassportWireContract::METHOD_PASSPORT_CONFIG]);
        self::assertSame('a929597a', PassportWireContract::OFFICIAL_IDS[PassportWireContract::METHOD_AUTHORIZATION_FORM]);
        self::assertSame('f3ed4c73', PassportWireContract::OFFICIAL_IDS[PassportWireContract::METHOD_ACCEPT_AUTHORIZATION]);
    }

    /**
     * The gap that started this cycle: the family is on the generated wire
     * closure, with the official ids, verbatim.
     */
    public function test_the_family_is_on_the_generated_wire_schema(): void
    {
        $lines = [];
        foreach (UserScopeSchema::LINES as $line) {
            $lines[] = $line;
        }

        $expected = [
            'auth.importAuthorization#a57a7dad id:long bytes:bytes = auth.Authorization',
            'auth.exportAuthorization#e5bfffcd dc_id:int = auth.ExportedAuthorization',
            'help.getPassportConfig#c661ad08 hash:int = help.PassportConfig',
            'account.getAuthorizationForm#a929597a bot_id:long scope:string public_key:string = account.AuthorizationForm',
            'account.acceptAuthorization#f3ed4c73 bot_id:long scope:string public_key:string value_hashes:Vector<SecureValueHash> credentials:SecureCredentialsEncrypted = Bool',
        ];

        foreach ($expected as $line) {
            self::assertContains($line, $lines, 'missing official layer-227 line: '.$line);
        }
    }

    public function test_import_dispatches_with_the_official_method_and_exactly_two_params(): void
    {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_IMPORT_AUTHORIZATION => [
                [
                    '_' => 'auth.authorization',
                    'flags' => 0,
                    'user' => ['_' => 'user', 'id' => 4242, 'first_name' => 'Ada', 'username' => 'ada'],
                ],
            ],
        ]);

        $imported = (new AuthorizationImporter($wire))->import(77, 'blob-bytes');

        self::assertInstanceOf(ImportedAuthorization::class, $imported);
        self::assertSame(4242, $imported->userId);
        self::assertSame('4242', $imported->userIdString());
        self::assertFalse($imported->hasTermsOfService());

        $dispatch = $wire->firstDispatchOf(PassportWireContract::METHOD_IMPORT_AUTHORIZATION);
        self::assertNotNull($dispatch, 'auth.importAuthorization was never dispatched');
        self::assertSame('auth.importAuthorization', $dispatch['method']);
        self::assertSame(['id' => 77, 'bytes' => 'blob-bytes'], $dispatch['params']);
        self::assertSame(1, $wire->dispatchCount());
    }

    public function test_import_refuses_a_zero_id_before_dispatching_anything(): void
    {
        $wire = new MemoryPassportWire;

        try {
            (new AuthorizationImporter($wire))->import(0, 'blob');
            self::fail('a zero authorization id must be refused loud');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('positive authorization id', $e->getMessage());
        }

        self::assertSame(0, $wire->dispatchCount(), 'nothing may be dispatched for a refused id');
    }

    public function test_import_refuses_empty_bytes_before_dispatching_anything(): void
    {
        $wire = new MemoryPassportWire;

        $this->expectException(PassportLoginException::class);
        try {
            (new AuthorizationImporter($wire))->import(5, '');
        } finally {
            self::assertSame(0, $wire->dispatchCount(), 'nothing may be dispatched for empty bytes');
        }
    }

    public function test_a_sign_up_demand_is_refused_not_treated_as_a_login(): void
    {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_IMPORT_AUTHORIZATION => [
                [
                    '_' => 'auth.authorizationSignUpRequired',
                    'terms_of_service' => ['_' => 'help.termsOfService', 'min_age_confirm' => 0],
                ],
            ],
        ]);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessageMatches('/authorizationSignUpRequired/');
        (new AuthorizationImporter($wire))->import(5, 'blob');
    }

    public function test_a_user_without_an_id_is_refused_never_bound_as_zero(): void
    {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_IMPORT_AUTHORIZATION => [
                ['_' => 'auth.authorization', 'flags' => 0, 'user' => ['_' => 'user', 'first_name' => 'No Id']],
            ],
        ]);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessageMatches('/no usable id/');
        (new AuthorizationImporter($wire))->import(5, 'blob');
    }

    /**
     * The 2FA route depends on this: a PASSWORD_REQUIRED refusal must leave the
     * importer as a TYPED refusal, not flattened into a generic domain error.
     */
    public function test_password_required_survives_the_importer_as_itself(): void
    {
        $refusal = new RpcErrorException('PASSWORD_REQUIRED', 400, 'A 2FA password must be configured to use Telegram Passport.', 'auth.importAuthorization');
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_IMPORT_AUTHORIZATION => [$refusal],
        ]);

        try {
            (new AuthorizationImporter($wire))->import(5, 'blob');
            self::fail('a PASSWORD_REQUIRED refusal must not be swallowed');
        } catch (RpcErrorException $e) {
            self::assertSame('PASSWORD_REQUIRED', $e->rpcErrorMessage);
            self::assertTrue(PassportRefusal::passwordRequired($e));
        }
    }

    public function test_the_importer_reports_the_credential_free_projection_only(): void
    {
        $imported = new ImportedAuthorization(
            9,
            ['_' => 'user', 'id' => 9, 'first_name' => 'Ada', 'last_name' => 'L', 'username' => 'ada'],
            ['_' => 'auth.authorization', 'flags' => 1, 'terms_of_service' => ['_' => 'help.termsOfService']],
        );

        $array = $imported->toArray();

        self::assertTrue($imported->hasTermsOfService());
        self::assertSame(9, $array['user_id']);
        self::assertSame('Ada', $array['first_name']);
        self::assertTrue($array['terms_of_service']);
        self::assertArrayNotHasKey('user', $array, 'the raw wire array must not leak into a rendered/logged shape');
    }

    public function test_field_error_parses_both_constructor_shapes(): void
    {
        $data = PassportFieldError::fromResponse([
            '_' => 'secureValueErrorData',
            'type' => 'phone',
            'data_hash' => 'x',
            'field' => 'phone_number',
            'text' => 'The phone number is invalid.',
        ]);
        $front = PassportFieldError::fromResponse([
            '_' => 'secureValueErrorFrontSide',
            'type' => 'passport',
            'file_hash' => 'y',
            'text' => 'Front side unreadable.',
        ]);

        self::assertSame('phone_number', $data->field);
        self::assertSame('phone.phone_number: The phone number is invalid.', $data->label());
        // No field member ⇒ the constructor name is the label, never an empty one.
        self::assertSame('secureValueErrorFrontSide', $front->field);
        self::assertSame('passport.secureValueErrorFrontSide: Front side unreadable.', $front->label());
    }
}
