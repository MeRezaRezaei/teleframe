<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use PHPUnit\Framework\TestCase;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\{TlParam, TlConstructor, TlType, TlMethod, TlScheme};

final class TlMetamodelTest extends TestCase
{
    public function test_param_kinds(): void
    {
        self::assertSame('scalar', (new TlParam('user_id', 'long'))->kind());
        self::assertSame('string', (new TlParam('q', 'string'))->baseType());
        self::assertSame('nat', (new TlParam('flags', '#'))->kind());
        self::assertSame('true', (new TlParam('verified', 'flags.3?true'))->kind());
        $p = new TlParam('statuses', 'flags.14?Vector<UserStatus>');
        self::assertSame('vector', $p->kind());
        self::assertSame('UserStatus', $p->baseType());
        self::assertSame(['flags', 14], $p->conditional());
        self::assertNull((new TlParam('id', 'long'))->conditional());
        self::assertTrue((new TlParam('chat', '%Chat'))->isBare());
        self::assertFalse((new TlParam('chat', 'Chat'))->isBare());
    }

    public function test_backcompat_param(): void
    {
        $p = new TlParam('random_id', '{long}');
        self::assertSame('scalar', $p->kind());
        self::assertSame('long', $p->baseType());
    }

    public function test_constructor_namespacing_and_canonical(): void
    {
        $c = new TlConstructor('messages.dialogsSlice', 0x1234, [new TlParam('count', 'int')], 'messages.Dialogs');
        self::assertSame('messages', $c->namespace());
        self::assertSame('dialogsSlice', $c->shortName());
        self::assertSame('messages', $c->resultNamespace());
        self::assertSame('Dialogs', $c->resultShortName());
        self::assertSame('messages.dialogsSlice count:int = messages.Dialogs', $c->canonical());
        $params = $c->params();
        self::assertArrayHasKey('count', $params);
        self::assertSame('userEmpty id:long = User', (new TlConstructor('userEmpty', 0xb6e8176e, [new TlParam('id', 'long')], 'User'))->canonical());
    }

    public function test_type_union(): void
    {
        $t = new TlType('User');
        $t->addConstructor(new TlConstructor('userEmpty', 0xb6e8176e, [new TlParam('id', 'long')], 'User'));
        $t->addConstructor(new TlConstructor('user', 0x3bc0ae14, [], 'User'));
        self::assertTrue($t->isUnion());
        self::assertCount(2, $t->constructors());
        self::assertSame('User', $t->shortName());
        self::assertNull($t->namespace());
        $ns = new TlType('messages.Dialogs');
        self::assertSame('messages', $ns->namespace());
        self::assertSame('Dialogs', $ns->shortName());
    }

    public function test_method_and_scheme_counts(): void
    {
        $s = new TlScheme(212);
        $user = new TlType('User');
        $user->addConstructor(new TlConstructor('userEmpty', 0xb6e8176e, [new TlParam('id', 'long')], 'User'));
        $s->addType($user);
        $s->addMethod(new TlMethod('help.getConfig', 0xc4f9186b, [], 'help.Config'));
        self::assertSame(['types' => 2, 'constructors' => 1, 'methods' => 1], $s->counts());
        self::assertTrue($s->types()['User']->isUnion());
        self::assertArrayHasKey('help.getConfig', $s->methods());
        self::assertSame('help.getConfig = help.Config', $s->methods()['help.getConfig']->canonical());
        self::assertSame(212, $s->layer);
    }

    public function test_generic_param(): void
    {
        $p = new TlParam('X', '{X:Type}');
        self::assertSame('generic', $p->kind());
        $q = new TlParam('query', '!X');
        self::assertSame('ref', $q->kind());
        self::assertTrue($q->isAny());
        self::assertSame('X', $q->baseType());
    }

    public function test_last_wins_replacement(): void
    {
        $s = new TlScheme(8);
        $s->addConstructor(new TlConstructor('decryptedMessage', 0x11111111, [new TlParam('message', 'string')], 'DecryptedMessage'));
        $s->addConstructor(new TlConstructor('decryptedMessage', 0x22222222, [new TlParam('ttl', 'int')], 'DecryptedMessage'));
        $ctors = $s->types()['DecryptedMessage']->constructors();
        self::assertCount(1, $ctors);
        self::assertSame(0x22222222, $ctors['decryptedMessage']->id);
        self::assertArrayHasKey('ttl', $ctors['decryptedMessage']->params());
    }
}
