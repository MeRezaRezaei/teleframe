<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Tests\Support;

use MeRezaRezaei\Teleframe\Core\Support\EnvFile;
use PHPUnit\Framework\TestCase;

class EnvFileTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = tempnam(sys_get_temp_dir(), 'envtest');
        file_put_contents($this->tmp, "# comment\nA=1\nB=\"quoted value\"\n\nC=\"old\"\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->tmp);
    }

    public function testReadParsesValuesIncludingQuoted(): void
    {
        $this->assertSame('1', EnvFile::read($this->tmp)['A'] ?? '');
        $this->assertSame('quoted value', EnvFile::read($this->tmp)['B'] ?? '');
    }

    public function testUpsertReplacesExistingAndAddsNew(): void
    {
        EnvFile::upsert($this->tmp, 'C', 'new');
        EnvFile::upsert($this->tmp, 'D', 'added');
        $vars = EnvFile::read($this->tmp);
        $this->assertSame('new', $vars['C']);
        $this->assertSame('added', $vars['D']);
        $this->assertSame('1', $vars['A']); // untouched lines survive
    }

    public function testReadMissingFileReturnsEmpty(): void
    {
        $this->assertSame([], EnvFile::read('/nonexistent/env'));
    }

    public function testUpsertRewritesEveryDuplicateKeyLine(): void
    {
        file_put_contents($this->tmp, "K=\"old\"\nJ=\"keep\"\nK=\"stale\"\n");
        EnvFile::upsert($this->tmp, 'K', 'fresh');
        $this->assertSame('fresh', EnvFile::read($this->tmp)['K']); // read() returns the fresh value
        $this->assertSame('keep', EnvFile::read($this->tmp)['J']); // intervening lines survive
        $contents = (string) file_get_contents($this->tmp);
        $this->assertSame(2, substr_count($contents, 'K="fresh"')); // BOTH occurrences rewritten
        $this->assertSame(0, substr_count($contents, '"old"'));
        $this->assertSame(0, substr_count($contents, '"stale"'));
    }

    public function testReadOnMalformedLineThrowsInvalidFileException(): void
    {
        file_put_contents($this->tmp, "A=1\nthis line has no equals\n");
        $this->expectException(\Dotenv\Exception\InvalidFileException::class);
        EnvFile::read($this->tmp);
    }

    /**
     * A value carrying a quote AND a newline must stay ONE key.
     *
     * The writer emits KEY="value" in a double-quoted dotenv string, so an
     * unescaped `"` closed the value and everything after it became forged
     * KEY=value lines — reachable from any CLI value an operator can type
     * (VaultUseDefaultCommand writes one). Escaping the quote confines the
     * whole value, newlines included.
     */
    public function testUpsertCannotForgeASecondKeyThroughQuoteAndNewline(): void
    {
        file_put_contents($this->tmp, "APP_KEY=\"base64:keep\"\n");

        EnvFile::upsert($this->tmp, 'TELEFRAME_DEFAULT_ACCOUNT_ID', "123\"\nTELEGRAM_API_HASH=\"forged\"\nDB_PASSWORD=\"pwn\"");

        $vars = EnvFile::read($this->tmp);

        // One key, holding the whole value verbatim.
        $this->assertSame('123"'.PHP_EOL.'TELEGRAM_API_HASH="forged"'.PHP_EOL.'DB_PASSWORD="pwn"', $vars['TELEFRAME_DEFAULT_ACCOUNT_ID'] ?? null);
        $this->assertArrayNotHasKey('TELEGRAM_API_HASH', $vars, 'a forged key must never parse');
        $this->assertArrayNotHasKey('DB_PASSWORD', $vars, 'a forged key must never parse');
        // The pre-existing value is untouched.
        $this->assertSame('base64:keep', $vars['APP_KEY'] ?? null);
    }

    public function testUpsertEscapesBackslashesSoEscapingIsReversible(): void
    {
        EnvFile::upsert($this->tmp, 'WINDOWS_PATH', 'C:\\Users\\"me"\\bin');

        $this->assertSame('C:\\Users\\"me"\\bin', EnvFile::read($this->tmp)['WINDOWS_PATH'] ?? null);
    }
}
