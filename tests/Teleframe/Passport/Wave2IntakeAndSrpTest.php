<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Teleframe\Passport;

use MeRezaRezaei\Teleframe\Passport\Intake\MtprotoIntake;
use MeRezaRezaei\Teleframe\Passport\Intake\PassportIntake;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PasswordConfirmerContract;
use MeRezaRezaei\Teleframe\Passport\Srp\LiveSrp;
use PHPUnit\Framework\TestCase;

final class Wave2IntakeAndSrpTest extends TestCase
{
    public function test_mtproto_intake_implements_contract_and_extracts(): void
    {
        $intake = new MtprotoIntake;
        self::assertInstanceOf(PassportIntake::class, $intake);

        $update = [
            'message' => [
                'passport_data' => [
                    'credentials' => ['data' => 'd', 'hash' => 'h', 'secret' => 's'],
                ],
            ],
        ];

        self::assertTrue($intake->carriesCredentials($update));
        self::assertNotNull($intake->extract($update));
    }

    public function test_live_srp_implements_confirmer_contract(): void
    {
        $srp = new LiveSrp;
        self::assertInstanceOf(PasswordConfirmerContract::class, $srp);
    }

    public function test_live_srp_refuses_without_scope(): void
    {
        $srp = new LiveSrp;
        $this->expectException(PassportLoginException::class);
        $srp->confirm('password');
    }
}
