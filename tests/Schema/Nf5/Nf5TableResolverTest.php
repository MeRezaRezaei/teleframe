<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5Catalog;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5FieldDecomposer;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5TableResolver;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

final class Nf5TableResolverTest extends TestCase
{
    private function catalog(): Nf5Catalog
    {
        // T4f: 3-up from tests/Schema/Nf5/
        $scheme = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        return Nf5Catalog::load(__DIR__.'/../../../docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json', $scheme);
    }

    public function test_stage0_five_parents_resolve(): void
    {
        $catalog = $this->catalog();
        $scheme  = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        $resolver = new Nf5TableResolver($catalog, $scheme);
        $parents = $resolver->resolveAll(['tf_users', 'tf_messages', 'tf_messages_service', 'tf_message_medias', 'tf_message_entities']);

        self::assertCount(5, $parents);
        $names = array_map(fn ($t) => $t->tfName, $parents);
        self::assertSame(['tf_users','tf_messages','tf_messages_service','tf_message_medias','tf_message_entities'], $names);

        // tf_messages_service has 'action' child table with FK→MessageAction
        // T4a: correct table name is tf_message_actions (from catalog)
        $msgSvc = $parents[2];
        $childNames = array_map(fn ($c) => $c->tfName, $msgSvc->children);
        self::assertContains('tf_message_actions', $childNames);
        $actionChild = $msgSvc->children[array_search('tf_message_actions', $childNames)];
        self::assertTrue($actionChild->parent); // T4b: mirror tables always parent=true
        // T4g: no assertArrayHasKey on list — use array_column instead
        $actionColNames = array_column($actionChild->columns, 'name');
        self::assertContains('constructor', $actionColNames);
    }

    public function test_account_id_is_first_pk_column_in_every_table(): void
    {
        $catalog = $this->catalog();
        $scheme  = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        $resolver = new Nf5TableResolver($catalog, $scheme);
        $parents = $resolver->resolveAll(['tf_users', 'tf_messages']);

        foreach ($parents as $parent) {
            self::assertSame('account_id', $parent->columns[0]->name);
            foreach ($parent->children as $child) {
                self::assertSame('account_id', $child->columns[0]->name);
            }
        }
    }

    public function test_multi_ctor_tables_get_discriminator_column(): void
    {
        $catalog = $this->catalog();
        $scheme  = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        $resolver = new Nf5TableResolver($catalog, $scheme);
        $parents = $resolver->resolveAll(['tf_chats', 'tf_dialogs']);

        // tf_chats has 5 ctors → constructor column at index 2 (account_id, id, constructor)
        $chats = $parents[0];
        self::assertSame('constructor', $chats->columns[2]->name);
        // T4c: tf_dialogs has 2 ctors → constructor column at index 3 (account_id, peer_type, peer_id, constructor)
        $dialogs = $parents[1];
        self::assertSame('constructor', $dialogs->columns[3]->name);
    }

    public function test_usernames_vector_child_is_positioned(): void
    {
        $catalog = $this->catalog();
        $scheme  = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        $resolver = new Nf5TableResolver($catalog, $scheme);
        $parents = $resolver->resolveAll(['tf_users']);
        $users = $parents[0];

        $names = array_map(fn ($c) => $c->tfName, $users->children);
        self::assertContains('tf_users_usernames', $names);
        $unames = $users->children[array_search('tf_users_usernames', $names)];
        self::assertTrue($unames->positioned);
        $positionCol = array_filter($unames->columns, fn ($c) => $c->name === 'position');
        self::assertCount(1, $positionCol);
    }
}
