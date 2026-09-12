<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFieldDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

final class MirrorTableResolverTest extends TestCase
{
    private function catalog(): MirrorCatalog
    {
        // T4f: 3-up from tests/Schema/Mirror/
        $scheme = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        return MirrorCatalog::load(__DIR__.'/../../../docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json', $scheme);
    }

    public function test_stage0_five_parents_resolve(): void
    {
        $catalog = $this->catalog();
        $scheme  = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        $resolver = new MirrorTableResolver($catalog, $scheme);
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
        $resolver = new MirrorTableResolver($catalog, $scheme);
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
        $resolver = new MirrorTableResolver($catalog, $scheme);
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
        $resolver = new MirrorTableResolver($catalog, $scheme);
        $parents = $resolver->resolveAll(['tf_users']);
        $users = $parents[0];

        $names = array_map(fn ($c) => $c->tfName, $users->children);
        self::assertContains('tf_users_usernames', $names);
        $unames = $users->children[array_search('tf_users_usernames', $names)];
        self::assertTrue($unames->positioned);
        $positionCol = array_filter($unames->columns, fn ($c) => $c->name === 'position');
        self::assertCount(1, $positionCol);
    }

    public function test_keyColumns_content(): void
    {
        $catalog = $this->catalog();
        $scheme  = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        $resolver = new MirrorTableResolver($catalog, $scheme);
        $parents = $resolver->resolveAll(['tf_users', 'tf_dialogs']);

        // tf_users: non-empty base, first base entry is not 'peer' → id-based → keyColumns = ['id']
        $users = $parents[0];
        self::assertSame('tf_users', $users->tfName);
        self::assertSame(['id'], $users->keyColumns);

        // tf_dialogs: base starts with 'peer' → peer-based → keyColumns = ['peer_type', 'peer_id']
        $dialogs = $parents[1];
        self::assertSame('tf_dialogs', $dialogs->tfName);
        self::assertSame(['peer_type', 'peer_id'], $dialogs->keyColumns);

        // child of tf_dialogs inherits the parent's keyColumns
        self::assertNotEmpty($dialogs->children);
        $child = $dialogs->children[0];
        self::assertSame(['peer_type', 'peer_id'], $child->keyColumns);
    }

    public function test_scalar_catalog_children_materialize_as_fact_tables(): void
    {
        $catalog = $this->catalog();
        $scheme  = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        $resolver = new MirrorTableResolver($catalog, $scheme);
        $parents = $resolver->resolveAll(['tf_bot_infos', 'tf_bot_inline_results', 'tf_business_chat_links']);

        // Optional fields declared as scalar (non-FK, non-vector) children must
        // become 1:1 child fact tables — NOT be silently dropped.
        $expected = [
            'tf_bot_infos' => ['tf_bot_infos_user_id', 'tf_bot_infos_description', 'tf_bot_infos_privacy_policy_url'],
            'tf_bot_inline_results' => ['tf_bot_inline_results_title', 'tf_bot_inline_results_description', 'tf_bot_inline_results_url'],
            'tf_business_chat_links' => ['tf_business_chat_links_title'],
        ];
        foreach ($parents as $parent) {
            $childNames = array_map(fn ($c) => $c->tfName, $parent->children);
            foreach ($expected[$parent->tfName] as $wanted) {
                self::assertContains($wanted, $childNames, "scalar child {$wanted} of {$parent->tfName}");
            }
        }
        // Scalar children carry the parent key + the declared scalar column only.
        $infos = $parents[0];
        $userId = $infos->children[array_search('tf_bot_infos_user_id', array_map(fn ($c) => $c->tfName, $infos->children))];
        self::assertSame(['bot_info_id'], $userId->keyColumns);
        $cols = array_column($userId->columns, 'name');
        self::assertSame(['account_id', 'bot_info_id', 'user_id'], $cols);
    }

    public function test_natural_id_base_fields_become_keys_not_synthetic(): void
    {
        $catalog = $this->catalog();
        $scheme  = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        $resolver = new MirrorTableResolver($catalog, $scheme);

        // base[0] ending in `_id` (shortcut_id, bot_id, user_id) is a real TL
        // natural key. Using it as PK must replace the fabricated {singular}_id.
        $expectedKeys = [
            'tf_attach_menu_bots'     => ['bot_id'],
            'tf_channel_participants' => ['user_id'],
            'tf_quick_replies'        => ['shortcut_id'],
        ];
        $fabricated = [
            'tf_attach_menu_bots'     => 'attach_menu_bot_id',
            'tf_channel_participants' => 'channel_participant_id',
            'tf_quick_replies'        => 'quick_replie_id',
        ];
        foreach ($expectedKeys as $tf => $expected) {
            $t = $resolver->resolveAll([$tf])[0];
            self::assertSame($expected, $t->keyColumns, "natural key for {$tf}");
            $colNames = array_column($t->columns, 'name');
            self::assertContains($expected[0], $colNames, "natural key column present in {$tf}");
            self::assertNotContains($fabricated[$tf], $colNames, "fabricated column removed from {$tf}");
        }
    }

    public function test_tagged_columns_populated(): void
    {
        $catalog = $this->catalog();
        $scheme  = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        $resolver = new MirrorTableResolver($catalog, $scheme);
        $parents = $resolver->resolveAll(['tf_users', 'tf_dialogs']);

        // tf_users: booleanColumns is list<string> — should contain catalog bools verbatim
        $users = $parents[0];
        self::assertNotEmpty($users->booleanColumns);
        self::assertContains('self', $users->booleanColumns);
        self::assertContains('deleted', $users->booleanColumns);
        self::assertContains('bot', $users->booleanColumns);

        // tf_dialogs: peerColumns should contain peer_type and peer_id
        $dialogs = $parents[1];
        self::assertNotEmpty($dialogs->peerColumns);
        $peerNames = array_column($dialogs->peerColumns, 'name');
        self::assertContains('peer_type', $peerNames);
        self::assertContains('peer_id', $peerNames);
    }
}
