<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;
use MeRezaRezaei\Teleframe\Schema\Mirror\Ddl\MirrorColumnType;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFieldDecomposer;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

final class MirrorFieldDecomposerTest extends TestCase
{
    public function test_bigint_shape(): void
    {
        $cols = MirrorFieldDecomposer::fromShape('BIGINT NOT NULL', 'id');
        self::assertCount(1, $cols);
        self::assertSame('id', $cols[0]->name);
        self::assertSame(MirrorColumnType::BigInt, $cols[0]->type);
        self::assertFalse($cols[0]->peer);
    }

    public function test_peer_shape(): void
    {
        $cols = MirrorFieldDecomposer::fromShape('peer_type TINYINT + peer_id BIGINT', 'from_id');
        self::assertCount(2, $cols);
        self::assertSame('from_id_type', $cols[0]->name);  // scoped under field name
        self::assertSame('from_id_id', $cols[1]->name);
        self::assertTrue($cols[0]->peer);
        self::assertSame(MirrorColumnType::TinyInt, $cols[0]->type);
        self::assertSame(MirrorColumnType::BigInt, $cols[1]->type);
    }

    public function test_varchar_shape(): void
    {
        $cols = MirrorFieldDecomposer::fromShape('VARCHAR(32) NOT NULL', 'username');
        self::assertSame(MirrorColumnType::String, $cols[0]->type);
        self::assertSame(32, $cols[0]->length);
    }

    public function test_fk_and_vector_shapes(): void
    {
        self::assertNull(MirrorFieldDecomposer::fromShape('FK→MessageFwdHeader', 'fwd_from'));
        // FK/Vector: resolver decides the decomposition; fromShape returns null marker.
    }

    public function test_text_shape(): void
    {
        $cols = MirrorFieldDecomposer::fromShape('TEXT NOT NULL', 'message');
        self::assertCount(1, $cols);
        self::assertSame(MirrorColumnType::String, $cols[0]->type);
        self::assertSame(0, $cols[0]->length);
    }

    public function test_double_shape(): void
    {
        $cols = MirrorFieldDecomposer::fromShape('DOUBLE PRECISION NOT NULL', 'latitude');
        self::assertCount(1, $cols);
        self::assertSame(MirrorColumnType::Double, $cols[0]->type);
    }

    public function test_integer_shape(): void
    {
        $cols = MirrorFieldDecomposer::fromShape('INTEGER NOT NULL', 'count');
        self::assertCount(1, $cols);
        self::assertSame(MirrorColumnType::Integer, $cols[0]->type);
    }

    public function test_unknown_shape_falls_back_to_bigint(): void
    {
        // UNKNOWN SHAPE falls back to BigInt — verifies the default fallback
        $cols = MirrorFieldDecomposer::fromShape('SOMETHING_ELSE', 'misc');
        self::assertCount(1, $cols);
        self::assertSame(MirrorColumnType::BigInt, $cols[0]->type);
    }

    public function test_boolean_shape(): void
    {
        $cols = MirrorFieldDecomposer::fromShape('BOOLEAN NOT NULL DEFAULT FALSE', 'flag');
        self::assertCount(1, $cols);
        self::assertSame(MirrorColumnType::Boolean, $cols[0]->type);
        self::assertSame('flag', $cols[0]->name);
    }

    public function test_shape_for_param_scalar_long(): void
    {
        $shape = MirrorFieldDecomposer::shapeForParam(new TlParam('message_id', 'long'));
        self::assertSame('BIGINT NOT NULL', $shape);
    }

    public function test_shape_for_param_bool_ref(): void
    {
        // ref to Bool / True maps to BOOLEAN, NOT FK
        $shape = MirrorFieldDecomposer::shapeForParam(new TlParam('post', 'Bool'));
        self::assertSame('BOOLEAN NOT NULL DEFAULT FALSE', $shape);
    }

    public function test_shape_for_param_true_kind(): void
    {
        // bare 'true' kind maps to BOOLEAN
        $shape = MirrorFieldDecomposer::shapeForParam(new TlParam('silent', 'true'));
        self::assertSame('BOOLEAN NOT NULL DEFAULT FALSE', $shape);
    }

    public function test_shape_for_param_ref_target(): void
    {
        $shape = MirrorFieldDecomposer::shapeForParam(new TlParam('fwd_from', 'MessageFwdHeader'));
        self::assertSame('FK→MessageFwdHeader', $shape);
    }

    public function test_shape_for_param_vector(): void
    {
        $shape = MirrorFieldDecomposer::shapeForParam(new TlParam('entities', 'Vector<MessageEntity>'));
        self::assertSame('1:N child', $shape);
    }

    public function test_shape_for_param_int_scalar(): void
    {
        $shape = MirrorFieldDecomposer::shapeForParam(new TlParam('flags', 'int'));
        self::assertSame('INTEGER NOT NULL', $shape);
    }

    public function test_vector_shape_returns_null(): void
    {
        self::assertNull(MirrorFieldDecomposer::fromShape('1:N child', 'messages'));
    }

    public function test_shape_for_param_unknown_falls_back_to_text(): void
    {
        // '#' (nat kind) hits the default branch in shapeForParam
        $shape = MirrorFieldDecomposer::shapeForParam(new TlParam('hash', '#'));
        self::assertSame('TEXT NOT NULL', $shape);
    }
}
