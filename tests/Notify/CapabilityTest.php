<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Notify\CapabilityCheck;
use MeRezaRezaei\Teleframe\Notify\CapabilityTable;
use MeRezaRezaei\Teleframe\Notify\PremiumStatus;
use PHPUnit\Framework\TestCase;

/**
 * Data-driven capability gate (design §6): the 8 default rows, merge()
 * semantics, effective() premium multipliers, and the verdicts the pipeline
 * consumes (allow / truncate / refuse) — every number comes from the table,
 * the checker only reads rows.
 */
final class CapabilityTest extends TestCase
{
    private const MEBIBYTE = 1048576;

    private const GIBIBYTE = 1073741824;

    public function test_table_ships_the_eight_default_rows(): void
    {
        $table = new CapabilityTable;

        self::assertCount(8, $table->rows());
        self::assertSame(4096, $table->effective('text_message', false));
        self::assertSame(1024, $table->effective('caption', false));
        self::assertSame(10, $table->effective('media_album', false));
        self::assertSame(50 * self::MEBIBYTE, $table->effective('file_upload_bot', false));
        self::assertSame(2 * self::GIBIBYTE, $table->effective('file_upload_mtproto', false));
        self::assertSame(30, $table->effective('send_rate', false));
        self::assertSame(1, $table->effective('per_chat_rate', false));
        self::assertSame(60, $table->effective('topic_budget', false));
    }

    public function test_merge_overrides_existing_rows_and_adds_new_ones(): void
    {
        $table = (new CapabilityTable)->merge([
            'file_upload_bot' => ['base' => 100, 'unit' => 'MiB', 'premiumMultiplier' => 1.0],
        ]);
        self::assertSame(100 * self::MEBIBYTE, $table->effective('file_upload_bot', false));

        $merged = $table->merge([
            ['feature' => 'custom_x', 'base' => 7, 'unit' => 'items', 'premiumMultiplier' => 2.0],
        ]);
        self::assertSame(7, $merged->effective('custom_x', false));
        self::assertSame(14, $merged->effective('custom_x', true));
        self::assertSame(100 * self::MEBIBYTE, $merged->effective('file_upload_bot', false));
    }

    public function test_effective_applies_premium_multipliers_from_the_rows(): void
    {
        $table = new CapabilityTable;

        self::assertSame(135, $table->effective('send_rate', true)); // 30 × 4.5
        self::assertSame(4 * self::GIBIBYTE, $table->effective('file_upload_mtproto', true)); // 2 GiB × 2
        self::assertSame(4096, $table->effective('text_message', true)); // ×1 — not premium-raised
        self::assertSame(270, $table->effective('topic_budget', true)); // 60 × 4.5
    }

    public function test_unknown_feature_throws_invalid_argument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unknown capability feature');

        (new CapabilityTable)->effective('no.such.feature', false);
    }

    public function test_check_text_over_the_cap_truncates(): void
    {
        $verdict = (new CapabilityCheck)->checkText(str_repeat('a', 4097), PremiumStatus::unknown());

        self::assertFalse($verdict->allowed());
        self::assertTrue($verdict->truncated());
        self::assertSame(4096, $verdict->effective());
        self::assertStringContainsString('exceeds', (string) $verdict->reason());
    }

    public function test_check_text_within_the_cap_allows(): void
    {
        $verdict = (new CapabilityCheck)->checkText('short brief', PremiumStatus::unknown());

        self::assertTrue($verdict->allowed());
        self::assertFalse($verdict->truncated());
        self::assertSame(4096, $verdict->effective());
        self::assertNull($verdict->reason());
    }

    public function test_check_file_bot_over_50_mib_refuses(): void
    {
        $verdict = (new CapabilityCheck)->checkFile(50 * self::MEBIBYTE + 1, PremiumStatus::fromBool(false), 'bot');

        self::assertFalse($verdict->allowed());
        self::assertFalse($verdict->truncated());
        self::assertStringContainsString('refusing', (string) $verdict->reason());
    }

    public function test_check_file_mtproto_premium_4_gib_allows(): void
    {
        $verdict = (new CapabilityCheck)->checkFile(4 * self::GIBIBYTE, PremiumStatus::fromBool(true), 'mtproto');

        self::assertTrue($verdict->allowed());
        self::assertSame(4 * self::GIBIBYTE, $verdict->effective());
    }

    public function test_check_file_mtproto_premium_over_4_gib_refuses(): void
    {
        $verdict = (new CapabilityCheck)->checkFile(4 * self::GIBIBYTE + 1, PremiumStatus::fromBool(true), 'mtproto');

        self::assertTrue($verdict->denied());
        self::assertSame(4 * self::GIBIBYTE, $verdict->effective());
    }

    public function test_check_rate_premium_135_allows_and_136_refuses(): void
    {
        $checker = new CapabilityCheck;

        self::assertTrue($checker->checkRate(135, PremiumStatus::fromBool(true))->allowed());
        self::assertTrue($checker->checkRate(136, PremiumStatus::fromBool(true))->denied());
        self::assertTrue($checker->checkRate(30, PremiumStatus::fromBool(false))->allowed());
        self::assertTrue($checker->checkRate(31, PremiumStatus::fromBool(false))->denied());
    }

    public function test_check_file_unknown_transport_throws_invalid_argument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unknown transport');

        (new CapabilityCheck)->checkFile(1, PremiumStatus::unknown(), 'carrier-pigeon');
    }
}
