<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use PHPUnit\Framework\TestCase;

/**
 * PassportConfigConfigParityTest — the two configuration paths must agree.
 *
 * THE GAP. `src/Laravel/config/passport.php` publishes `require_verified`,
 * `email_column` and `phone_column`, and PassportServiceProvider reads all
 * three. None of them had an accessor on `PassportConfig` and none appeared in
 * `PassportConfig::defaults()`, so a plain-PHP host doing
 * `new PassportConfig(['require_verified' => true])` had a config object that
 * silently ignored the key it had just been handed: the two paths could
 * disagree with no error anywhere. These tests pin the parity so it cannot
 * drift again.
 */
final class PassportConfigConfigParityTest extends TestCase
{
    /**
     * Every key the Laravel provider reads off `teleframe.passport.*`. Kept as
     * a literal list rather than importing the provider, because the provider
     * is the thing that is allowed to change; this list is the contract the
     * plain-PHP reader owes it.
     *
     * @return list<string>
     */
    private const LARAVEL_PUBLISHED_KEYS = [
        'enabled',
        'private_key_path',
        'account_id',
        'nonce_ttl',
        'nonce_store',
        'allowed_types',
        'user_lookup',
        'require_password_confirmation',
        'require_verified',
        'email_column',
        'phone_column',
        // The ANONYMOUS LOGIN capability and the two decisions that belong to
        // it. Published by the config file, read by the service and the routes,
        // so they owe the plain-PHP reader an accessor exactly as the field
        // mode keys above do.
        'anonymous_login',
        'unknown_user',
        'login_rate_limit',
    ];

    public function test_plain_php_config_reads_require_verified(): void
    {
        self::assertTrue((new PassportConfig(['require_verified' => true]))->requireVerified());
        self::assertFalse((new PassportConfig(['require_verified' => false]))->requireVerified());
    }

    public function test_require_verified_defaults_to_false_so_the_shipped_flow_is_inert(): void
    {
        // The Laravel config file's own default (require_verified => false):
        // a host that has never heard of the key must get the inert answer, or
        // enabling field mode would demand proof nobody asked for.
        self::assertFalse((new PassportConfig)->requireVerified());
    }

    public function test_plain_php_config_reads_the_field_mode_columns(): void
    {
        $config = new PassportConfig([
            'email_column' => 'users.email_address',
            'phone_column' => 'users.phone_number',
        ]);

        self::assertSame('users.email_address', $config->emailColumn());
        self::assertSame('users.phone_number', $config->phoneColumn());
    }

    public function test_the_field_mode_columns_default_to_the_providers_fallbacks(): void
    {
        // Mirrors PassportServiceProvider::registerIdentity, which falls back
        // to 'email' / 'phone' when the keys are absent.
        $config = new PassportConfig;

        self::assertSame('email', $config->emailColumn());
        self::assertSame('phone', $config->phoneColumn());
    }

    public function test_an_empty_column_name_falls_back_rather_than_producing_an_empty_lookup(): void
    {
        // A column of '' would build `where('', $value)` and match the first
        // row of the table — the wrong-user-login shape. Fall back instead.
        $config = new PassportConfig(['email_column' => '', 'phone_column' => '']);

        self::assertSame('email', $config->emailColumn());
        self::assertSame('phone', $config->phoneColumn());
    }

    /**
     * The parity claim itself: every key the Laravel side publishes is present
     * in the plain-PHP defaults, so a host can never configure a key that the
     * reader silently drops.
     */
    public function test_every_laravel_published_key_exists_in_the_plain_php_defaults(): void
    {
        $defaults = (new \ReflectionMethod(PassportConfig::class, 'defaults'));
        $defaults->setAccessible(true);
        $resolved = $defaults->invoke(null);

        self::assertIsArray($resolved);

        foreach (self::LARAVEL_PUBLISHED_KEYS as $key) {
            self::assertArrayHasKey(
                $key,
                $resolved,
                "teleframe.passport.{$key} is published by the Laravel config but missing from PassportConfig::defaults()"
            );
        }
    }

    /**
     * The three keys this gap was about are readable, not merely present in
     * defaults: an accessor is what makes a key configurable in practice.
     */
    /**
     * The anonymous login's three keys, readable and DEFAULTING TO THE INERT /
     * SAFE answer. The defaults are the point: a host that has never heard of
     * `anonymous_login` must get a login that cannot log anyone in, and a host
     * that has never heard of `unknown_user` must get the refusal.
     */
    public function test_the_anonymous_login_keys_are_reachable_and_default_to_the_safe_answer(): void
    {
        $defaults = new PassportConfig;

        self::assertFalse($defaults->anonymousLoginEnabled(), 'the login capability is OFF unless a host opts in');
        self::assertSame('refuse', $defaults->unknownUserPolicy(), 'an unmatched field refuses; it never provisions');
        self::assertSame(10, $defaults->loginRateLimit());

        $config = new PassportConfig([
            'anonymous_login' => true,
            'unknown_user' => 'refuse',
            'login_rate_limit' => 3,
        ]);

        self::assertTrue($config->anonymousLoginEnabled());
        self::assertSame('refuse', $config->unknownUserPolicy());
        self::assertSame(3, $config->loginRateLimit());
    }

    /** A blank or nonsensical policy value falls back to the safe one. */
    public function test_an_empty_unknown_user_policy_falls_back_to_refuse(): void
    {
        self::assertSame('refuse', (new PassportConfig(['unknown_user' => '']))->unknownUserPolicy());
        self::assertSame('refuse', (new PassportConfig(['unknown_user' => null]))->unknownUserPolicy());
    }

    /** A negative rate limit means "no package ceiling", never a negative one. */
    public function test_a_negative_rate_limit_is_folded_to_zero(): void
    {
        self::assertSame(0, (new PassportConfig(['login_rate_limit' => -5]))->loginRateLimit());
    }

    public function test_the_three_previously_missing_keys_are_reachable_through_accessors(): void
    {
        $config = new PassportConfig([
            'require_verified' => true,
            'email_column' => 'contact_email',
            'phone_column' => 'contact_phone',
        ]);

        self::assertTrue($config->requireVerified());
        self::assertSame('contact_email', $config->emailColumn());
        self::assertSame('contact_phone', $config->phoneColumn());
    }
}
