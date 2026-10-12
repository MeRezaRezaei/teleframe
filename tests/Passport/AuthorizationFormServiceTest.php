<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Passport\AuthorizationFormService;
use MeRezaRezaei\Teleframe\Passport\MemoryPassportWire;
use MeRezaRezaei\Teleframe\Passport\PassportAuthorizationForm;
use MeRezaRezaei\Teleframe\Passport\PassportFieldErrorsException;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportWireContract;
use PHPUnit\Framework\TestCase;

/**
 * AuthorizationFormService — the request/accept pair
 * `account.getAuthorizationForm#a929597a` / `account.acceptAuthorization#f3ed4c73`
 * (OFFICIAL layer-227 ids, gap analysis §3.1).
 *
 * The pinned behaviour is the success criterion verbatim: **SecureValueError
 * surfaces per-field errors.** Telegram reports a field refusal inside the
 * form (or inside a non-Bool accept answer) rather than as an rpc_error, so a
 * service that only looked at the constructor would either ignore it or claim
 * success. These tests pin that EVERY field is carried, in Telegram's order,
 * with Telegram's own text — and that a non-true accept is refused rather than
 * reported as a silent "shared".
 */
final class AuthorizationFormServiceTest extends TestCase
{
    private const CREDENTIALS = ['_' => 'secureCredentialsEncrypted', 'data' => 'd', 'hash' => 'h', 'secret' => 's'];

    public function test_request_dispatches_with_the_official_method_and_three_params(): void
    {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_AUTHORIZATION_FORM => [
                [
                    '_' => 'account.authorizationForm',
                    'flags' => 0,
                    'required_types' => ['personal_details', 'phone'],
                    'users' => [['_' => 'user', 'id' => 4242]],
                    'privacy_policy_url' => 'https://telegram.org/privacy',
                ],
            ],
        ]);

        $form = (new AuthorizationFormService($wire))->request(77, 'personal_details', 'PUBLIC-PEM');

        self::assertInstanceOf(PassportAuthorizationForm::class, $form);
        self::assertSame(4242, $form->userId());
        self::assertSame(['personal_details', 'phone'], $form->requiredTypes);
        self::assertSame('https://telegram.org/privacy', $form->privacyPolicyUrl);
        self::assertFalse($form->hasErrors());

        $dispatch = $wire->firstDispatchOf('account.getAuthorizationForm');
        self::assertNotNull($dispatch);
        self::assertSame('a929597a', PassportWireContract::OFFICIAL_IDS['account.getAuthorizationForm']);
        self::assertSame(
            ['bot_id' => 77, 'scope' => 'personal_details', 'public_key' => 'PUBLIC-PEM'],
            $dispatch['params']
        );
    }

    public function test_per_field_secure_value_errors_are_surfaced_in_order(): void
    {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_AUTHORIZATION_FORM => [
                [
                    '_' => 'account.authorizationForm',
                    'flags' => 0,
                    'users' => [['_' => 'user', 'id' => 5]],
                    'errors' => [
                        [
                            '_' => 'secureValueErrorData',
                            'type' => 'phone',
                            'data_hash' => 'x',
                            'field' => 'phone_number',
                            'text' => 'The phone number is invalid.',
                        ],
                        [
                            '_' => 'secureValueErrorFrontSide',
                            'type' => 'passport',
                            'file_hash' => 'y',
                            'text' => 'Front side is unreadable.',
                        ],
                    ],
                ],
            ],
        ]);

        $form = (new AuthorizationFormService($wire))->request(1, 'passport', 'PEM');

        self::assertTrue($form->hasErrors());
        self::assertCount(2, $form->fieldErrors, 'BOTH field errors are surfaced, not just the first');
        self::assertSame('phone_number', $form->fieldErrors[0]->field);
        self::assertSame('The phone number is invalid.', $form->fieldErrors[0]->text);
        self::assertSame('secureValueErrorFrontSide', $form->fieldErrors[1]->field);
        self::assertSame('passport.secureValueErrorFrontSide: Front side is unreadable.', $form->fieldErrors[1]->label());

        // assertNoFieldErrors is the loud form: it raises with every field.
        try {
            $form->assertNoFieldErrors('account.getAuthorizationForm');
            self::fail('field errors must be raiseable, not ignored');
        } catch (PassportFieldErrorsException $e) {
            self::assertSame(2, $e->count());
            self::assertStringContainsString('phone.phone_number', $e->describe());
            self::assertStringContainsString('passport.secureValueErrorFrontSide', $e->describe());
            self::assertInstanceOf(PassportLoginException::class, $e);
        }
    }

    public function test_a_form_that_is_not_a_form_is_refused_loud(): void
    {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_AUTHORIZATION_FORM => [['_' => 'boolTrue']],
        ]);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessageMatches('/instead of account.authorizationForm/');
        (new AuthorizationFormService($wire))->request(1, 'scope', 'PEM');
    }

    public function test_accept_dispatches_with_the_official_method_and_five_params(): void
    {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_ACCEPT_AUTHORIZATION => [['_' => 'boolTrue']],
        ]);

        $accepted = (new AuthorizationFormService($wire))->accept(77, 'personal_details', 'PEM', [
            ['_' => 'secureValueHash', 'type' => 'personal_details', 'hash' => 'h'],
        ], self::CREDENTIALS);

        self::assertTrue($accepted);

        $dispatch = $wire->firstDispatchOf('account.acceptAuthorization');
        self::assertNotNull($dispatch);
        self::assertSame('f3ed4c73', PassportWireContract::OFFICIAL_IDS['account.acceptAuthorization']);
        self::assertSame(
            ['bot_id', 'scope', 'public_key', 'value_hashes', 'credentials'],
            array_keys($dispatch['params'])
        );
    }

    public function test_a_per_field_refusal_inside_accept_surfaces_every_field(): void
    {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_ACCEPT_AUTHORIZATION => [[
                'errors' => [
                    ['_' => 'secureValueErrorData', 'type' => 'email', 'field' => 'email', 'text' => 'Email not verified.'],
                    ['_' => 'secureValueErrorData', 'type' => 'phone', 'field' => 'phone_number', 'text' => 'Phone not verified.'],
                ],
            ]],
        ]);

        try {
            (new AuthorizationFormService($wire))->accept(1, 'scope', 'PEM', [], self::CREDENTIALS);
            self::fail('a per-field refusal must not be reported as an accept');
        } catch (PassportFieldErrorsException $e) {
            self::assertSame(2, $e->count());
            self::assertStringContainsString('email.email: Email not verified.', $e->describe());
            self::assertStringContainsString('phone.phone_number: Phone not verified.', $e->describe());
            self::assertStringContainsString('account.acceptAuthorization', $e->getMessage());
        }
    }

    public function test_a_false_answer_is_refused_never_reported_as_shared(): void
    {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_ACCEPT_AUTHORIZATION => [['_' => 'boolFalse']],
        ]);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessageMatches('/instead of boolTrue/');
        (new AuthorizationFormService($wire))->accept(1, 'scope', 'PEM', [], self::CREDENTIALS);
    }
}
