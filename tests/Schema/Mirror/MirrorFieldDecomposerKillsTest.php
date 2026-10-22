<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;
use MeRezaRezaei\Teleframe\Schema\Mirror\Ddl\MirrorColumnType;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFieldDecomposer;
use PHPUnit\Framework\TestCase;

final class MirrorFieldDecomposerKillsTest extends TestCase
{
    public function test_from_shape_fk_and_child_return_null(): void
    {
        self::assertNull(MirrorFieldDecomposer::fromShape('FK→User', 'author_id'));
        self::assertNull(MirrorFieldDecomposer::fromShape('1:N child', 'participants'));
        self::assertNull(MirrorFieldDecomposer::fromShape('FK→Document', 'media'));
    }

    public function test_shape_for_param_true_kind(): void
    {
        $param = new TlParam('verified', 'flags.3?true');
        self::assertSame('BOOLEAN NOT NULL DEFAULT FALSE', MirrorFieldDecomposer::shapeForParam($param));
    }

    public function test_shape_for_param_scalar_kind(): void
    {
        $param = new TlParam('user_id', 'long');
        self::assertSame('BIGINT NOT NULL', MirrorFieldDecomposer::shapeForParam($param));

        $param2 = new TlParam('count', 'int');
        self::assertSame('INTEGER NOT NULL', MirrorFieldDecomposer::shapeForParam($param2));
    }

    public function test_shape_for_param_ref_kind(): void
    {
        $param = new TlParam('chat', 'Chat');
        self::assertSame('FK→Chat', MirrorFieldDecomposer::shapeForParam($param));

        $param2 = new TlParam('verified_flag', 'Bool');
        self::assertSame('BOOLEAN NOT NULL DEFAULT FALSE', MirrorFieldDecomposer::shapeForParam($param2));
    }

    public function test_shape_for_param_vector_kind(): void
    {
        $param = new TlParam('statuses', 'Vector<UserStatus>');
        self::assertSame('1:N child', MirrorFieldDecomposer::shapeForParam($param));
    }

    public function test_shape_for_param_default_kind(): void
    {
        $param = new TlParam('flags', '#');
        self::assertSame('TEXT NOT NULL', MirrorFieldDecomposer::shapeForParam($param));
    }

    public function test_scalar_column_varchar(): void
    {
        $col = MirrorFieldDecomposer::fromShape('VARCHAR(64) NOT NULL', 'title')[0];
        self::assertSame(MirrorColumnType::String, $col->type);
        self::assertSame(64, $col->length);

        $col0 = MirrorFieldDecomposer::fromShape('VARCHAR(0) NOT NULL', 'title')[0];
        // length 0 falls through; final BigInt fallback
        self::assertSame(MirrorColumnType::BigInt, $col0->type);

        $hex = MirrorFieldDecomposer::fromShape('VARCHAR(8) NOT NULL', 'title', true)[0];
        self::assertTrue($hex->hex);
    }

    public function test_scalar_column_text(): void
    {
        $col = MirrorFieldDecomposer::fromShape('TEXT NOT NULL', 'title')[0];
        self::assertSame(MirrorColumnType::String, $col->type);

        $notText = MirrorFieldDecomposer::fromShape('DOUBLE PRECISION NOT NULL', 'title')[0];
        self::assertNotSame(MirrorColumnType::String, $notText->type);
    }

    public function test_scalar_column_double(): void
    {
        $col = MirrorFieldDecomposer::fromShape('DOUBLE PRECISION NOT NULL', 'rate')[0];
        self::assertSame(MirrorColumnType::Double, $col->type);
    }

    public function test_scalar_column_tinyint(): void
    {
        $col = MirrorFieldDecomposer::fromShape('TINYINT NOT NULL', 'flag')[0];
        self::assertSame(MirrorColumnType::TinyInt, $col->type);
    }

    public function test_scalar_column_boolean(): void
    {
        $col = MirrorFieldDecomposer::fromShape('BOOLEAN NOT NULL DEFAULT FALSE', 'verified')[0];
        self::assertSame(MirrorColumnType::Boolean, $col->type);
    }

    public function test_scalar_column_bigint_hex(): void
    {
        $col = MirrorFieldDecomposer::fromShape('BIGINT NOT NULL', 'size', true)[0];
        self::assertSame(MirrorColumnType::BigInt, $col->type);
        self::assertTrue($col->hex);

        $col2 = MirrorFieldDecomposer::fromShape('BIGINT NOT NULL', 'size')[0];
        self::assertFalse($col2->hex);
    }

    public function test_scalar_column_integer(): void
    {
        $col = MirrorFieldDecomposer::fromShape('INTEGER NOT NULL', 'count')[0];
        self::assertSame(MirrorColumnType::Integer, $col->type);
    }

    public function test_scalar_shape_int(): void
    {
        $param = new TlParam('count', 'int');
        self::assertSame('INTEGER NOT NULL', MirrorFieldDecomposer::shapeForParam($param));
    }

    public function test_scalar_shape_long(): void
    {
        $param = new TlParam('user_id', 'long');
        self::assertSame('BIGINT NOT NULL', MirrorFieldDecomposer::shapeForParam($param));
    }

    public function test_scalar_shape_double(): void
    {
        $param = new TlParam('rate', 'double');
        self::assertSame('DOUBLE PRECISION NOT NULL', MirrorFieldDecomposer::shapeForParam($param));
    }

    public function test_scalar_shape_default(): void
    {
        $param = new TlParam('blob', 'int128');
        self::assertSame('TEXT NOT NULL', MirrorFieldDecomposer::shapeForParam($param));
    }

    public function test_hex_length_for_bytes(): void
    {
        $param = new TlParam('data', 'bytes');
        self::assertSame(0, MirrorFieldDecomposer::hexLengthForBytes($param));
    }
}
