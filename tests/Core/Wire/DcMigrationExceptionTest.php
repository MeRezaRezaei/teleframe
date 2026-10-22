<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\Wire;

use MeRezaRezaei\Teleframe\Core\Exceptions\DcMigrationException;
use PHPUnit\Framework\TestCase;

/**
 * The DC-migration token is a live device-auth credential.
 *
 * It has never been in the message (and must not be), and it is reachable
 * only through the explicit token() accessor now — the property bag is where
 * var_dump(), print_r(), a serialized exception and every error page that
 * dumps an exception object read from.
 */
final class DcMigrationExceptionTest extends TestCase
{
    private const TOKEN = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';

    public function test_the_token_is_not_in_the_message(): void
    {
        $e = new DcMigrationException(2, '', 303, self::TOKEN);

        self::assertStringNotContainsString(self::TOKEN, $e->getMessage());
        self::assertStringNotContainsString(self::TOKEN, (string) $e);
    }

    public function test_the_token_is_not_on_the_property_bag(): void
    {
        $e = new DcMigrationException(2, '', 303, self::TOKEN);

        $property = new \ReflectionProperty(DcMigrationException::class, 'token');
        self::assertFalse($property->isPublic(), 'the token must not be a public property');
        self::assertTrue($property->isPrivate());
        self::assertSame(self::TOKEN, $e->token(), 'the deliberate accessor still works');
    }

    public function test_var_dump_of_the_exception_never_renders_the_token(): void
    {
        $e = new DcMigrationException(2, '', 303, self::TOKEN);

        ob_start();
        var_dump($e);
        $dumped = (string) ob_get_clean();

        self::assertStringNotContainsString(self::TOKEN, $dumped);
        self::assertStringContainsString('hasToken', $dumped);
        self::assertStringContainsString('true', $dumped);
    }

    public function test_a_migration_without_a_token_still_works(): void
    {
        $e = new DcMigrationException(1);

        self::assertSame(1, $e->getDcId());
        self::assertNull($e->token());
        self::assertSame(303, $e->getCode());
        self::assertStringContainsString('DC_1', $e->getMessage());
    }
}
