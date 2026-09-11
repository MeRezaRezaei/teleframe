<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;
use MeRezaRezaei\Teleframe\Schema\Nf5\Ddl\Nf5ColumnType;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5FieldDecomposer;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

final class Nf5FieldDecomposerTest extends TestCase
{
    public function test_bigint_shape(): void
    {
        $cols = Nf5FieldDecomposer::fromShape('BIGINT NOT NULL', 'id');
        self::assertCount(1, $cols);
        self::assertSame('id', $cols[0]->name);
        self::assertSame(Nf5ColumnType::BigInt, $cols[0]->type);
        self::assertFalse($cols[0]->peer);
    }

    public function test_peer_shape(): void
    {
        $cols = Nf5FieldDecomposer::fromShape('peer_type TINYINT + peer_id BIGINT', 'from_id');
        self::assertCount(2, $cols);
        self::assertSame('from_id_type', $cols[0]->name);  // scoped under field name
        self::assertSame('from_id_id', $cols[1]->name);
        self::assertTrue($cols[0]->peer);
        self::assertSame(Nf5ColumnType::TinyInt, $cols[0]->type);
        self::assertSame(Nf5ColumnType::BigInt, $cols[1]->type);
    }

    public function test_varchar_shape(): void
    {
        $cols = Nf5FieldDecomposer::fromShape('VARCHAR(32) NOT NULL', 'username');
        self::assertSame(Nf5ColumnType::String, $cols[0]->type);
        self::assertSame(32, $cols[0]->length);
    }

    public function test_fk_and_vector_shapes(): void
    {
        self::assertNull(Nf5FieldDecomposer::fromShape('FK→MessageFwdHeader', 'fwd_from'));
        // FK/Vector: resolver decides the decomposition; fromShape returns null marker.
    }

    public function test_text_shape(): void
    {
        $cols = Nf5FieldDecomposer::fromShape('TEXT NOT NULL', 'message');
        self::assertCount(1, $cols);
        self::assertSame(Nf5ColumnType::String, $cols[0]->type);
        self::assertSame(0, $cols[0]->length);
    }

    public function test_double_shape(): void
    {
        $cols = Nf5FieldDecomposer::fromShape('DOUBLE PRECISION NOT NULL', 'latitude');
        self::assertCount(1, $cols);
        self::assertSame(Nf5ColumnType::Double, $cols[0]->type);
    }

    public function test_integer_shape(): void
    {
        $cols = Nf5FieldDecomposer::fromShape('INTEGER NOT NULL', 'count');
        self::assertCount(1, $cols);
        self::assertSame(Nf5ColumnType::Integer, $cols[0]->type);
    }

    public function test_unknown_shape_falls_back_to_bigint(): void
    {
        // UNKNOWN SHAPE falls back to BigInt — verifies the default fallback
        $cols = Nf5FieldDecomposer::fromShape('SOMETHING_ELSE', 'misc');
        self::assertCount(1, $cols);
        self::assertSame(Nf5ColumnType::BigInt, $cols[0]->type);
    }

    public function test_boolean_shape(): void
    {
        $cols = Nf5FieldDecomposer::fromShape('BOOLEAN NOT NULL DEFAULT FALSE', 'flag');
        self::assertCount(1, $cols);
        self::assertSame(Nf5ColumnType::Boolean, $cols[0]->type);
        self::assertSame('flag', $cols[0]->name);
    }

    public function test_shape_for_param_scalar_long(): void
    {
        $shape = Nf5FieldDecomposer::shapeForParam(new TlParam('message_id', 'long'));
        self::assertSame('BIGINT NOT NULL', $shape);
    }

    public function test_shape_for_param_bool_ref(): void
    {
        // ref to Bool / True maps to BOOLEAN, NOT FK
        $shape = Nf5FieldDecomposer::shapeForParam(new TlParam('post', 'Bool'));
        self::assertSame('BOOLEAN NOT NULL DEFAULT FALSE', $shape);
    }

    public function test_shape_for_param_true_kind(): void
    {
        // bare 'true' kind maps to BOOLEAN
        $shape = Nf5FieldDecomposer::shapeForParam(new TlParam('silent', 'true'));
        self::assertSame('BOOLEAN NOT NULL DEFAULT FALSE', $shape);
    }

    public function test_shape_for_param_ref_target(): void
    {
        $shape = Nf5FieldDecomposer::shapeForParam(new TlParam('fwd_from', 'MessageFwdHeader'));
        self::assertSame('FK→MessageFwdHeader', $shape);
    }

    public function test_shape_for_param_vector(): void
    {
        $shape = Nf5FieldDecomposer::shapeForParam(new TlParam('entities', 'Vector<MessageEntity>'));
        self::assertSame('1:N child', $shape);
    }

    public function test_shape_for_param_int_scalar(): void
    {
        $shape = Nf5FieldDecomposer::shapeForParam(new TlParam('flags', 'int'));
        self::assertSame('INTEGER NOT NULL', $shape);
    }

    public function test_vector_shape_returns_null(): void
    {
        self::assertNull(Nf5FieldDecomposer::fromShape('1:N child', 'messages'));
    }

    public function test_shape_for_param_unknown_falls_back_to_text(): void
    {
        // '#' (nat kind) hits the default branch in shapeForParam
        $shape = Nf5FieldDecomposer::shapeForParam(new TlParam('hash', '#'));
        self::assertSame('TEXT NOT NULL', $shape);
    }
}
