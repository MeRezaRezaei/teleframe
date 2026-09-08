<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use PHPUnit\Framework\TestCase;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;
use MeRezaRezaei\Teleframe\Schema\Generator\Naming;

final class NamingTest extends TestCase
{
    public function test_anchor_table(): void
    {
        self::assertSame('tl_user', Naming::anchorTable('User'));
        self::assertSame('tl_updates_state', Naming::anchorTable('updates.state'));
        self::assertSame('tl_messages_messages', Naming::anchorTable('messages.Messages'));
    }

    public function test_instance_table(): void
    {
        self::assertSame('tl_user_user', Naming::instanceTable('User', 'user'));
        self::assertSame('tl_user_user_empty', Naming::instanceTable('User', 'userEmpty'));
        self::assertSame('tl_messages_dialogs_dialogs_slice', Naming::instanceTable('messages.Dialogs', 'messages.dialogsSlice'));
    }

    public function test_child_table(): void
    {
        self::assertSame('tl_user_user__statuses', Naming::childTable('tl_user_user', 'statuses'));
    }

    public function test_column_escapes(): void
    {
        self::assertSame('tl_id', Naming::column('id'));
        self::assertSame('tl_type', Naming::column('type'));
        self::assertSame('tl_default', Naming::column('default'));
        self::assertSame('tl_order', Naming::column('order'));
        self::assertSame('tl_operator', Naming::column('operator'));
        self::assertSame('tl_long', Naming::column('long'));
        self::assertSame('first_name', Naming::column('first_name'));
        self::assertSame('date', Naming::column('date'));
        self::assertSame('until_date', Naming::column('until_date'));
    }

    public function test_model_names(): void
    {
        self::assertSame('TlUser', Naming::model('User'));
        self::assertSame('TlUpdatesState', Naming::model('updates.state'));
        self::assertSame('TlUserUser', Naming::ctorModel('User', 'user'));
        self::assertSame('TlUserUserEmpty', Naming::ctorModel('User', 'userEmpty'));
        self::assertSame('TlMessagesDialogsDialogsSlice', Naming::ctorModel('messages.Dialogs', 'messages.dialogsSlice'));
    }

    public function test_data_names(): void
    {
        self::assertSame('UserData', Naming::dataClass('user'));
        self::assertSame('TlMessagesSendMessageData', Naming::dataClass('messages.sendMessage'));
        self::assertSame('TlUserAbstractData', Naming::abstractDataClass('User'));
        self::assertSame('TlMessagesDialogsAbstractData', Naming::abstractDataClass('messages.Dialogs'));
    }

    public function test_db_types_and_casts(): void
    {
        $map = [
            ['int', 'integer', 'int'],
            ['long', 'bigint', 'int'],
            ['int128', 'numeric', 'string'],
            ['int256', 'numeric', 'string'],
            ['double', 'double', 'float'],
            ['string', 'text', 'string'],
            ['bytes', 'binary', 'string'],
            ['#', 'bigint', 'int'],
            ['true', 'boolean', 'bool'],
        ];
        foreach ($map as [$tl, $db, $cast]) {
            self::assertSame($db, Naming::dbType(new TlParam('p', $tl)), "dbType for {$tl}");
            self::assertSame($cast, Naming::cast(new TlParam('p', $tl)), "cast for {$tl}");
        }
        self::assertSame('numeric(39,0)', Naming::dbType(new TlParam('p', 'int128'), precision: true));
        self::assertSame('numeric(78,0)', Naming::dbType(new TlParam('p', 'int256'), precision: true));
        self::assertSame('uuid', Naming::dbType(new TlParam('p', 'User')));
        self::assertSame('string', Naming::cast(new TlParam('p', 'User')));
    }

    public function test_vector_db_type_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('child table');
        Naming::dbType(new TlParam('p', 'Vector<UserStatus>'));
    }

    public function test_assert_unique(): void
    {
        Naming::assertUnique(['a', 'b'], 'table');
        $this->expectException(\RuntimeException::class);
        Naming::assertUnique(['a', 'a'], 'table');
    }
}
