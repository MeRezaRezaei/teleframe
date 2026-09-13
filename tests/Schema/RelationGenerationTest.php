<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScope;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerIdTool;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfChannelParticipant;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfUser;

/**
 * Shape assertions against the NF5 mirror tf_* parent models (P2 shipped
 * surface). Mirror models are account-scoped relational projections of the
 * committed catalog: every parent boots the global AccountScope; peer
 * relations live on tl_* DTO side (peer-long scalars), so mirror models
 * expose no generated Eloquent relations.
 */
final class RelationGenerationTest extends TestCase
{
    public function test_every_generated_model_boots_account_scope(): void
    {
        foreach ([TfUser::class, TfMessage::class, TfChannelParticipant::class] as $class) {
            $model = new $class;
            $scopes = $model->getGlobalScopes();
            self::assertArrayHasKey(AccountScope::class, $scopes, "{$class} must boot the global AccountScope");
        }
    }

    public function test_peer_id_tool_separates_canonical_longs(): void
    {
        // Canonical long separation: a user id and a channel id must never collide.
        self::assertNotSame(
            PeerIdTool::userLong(501558149),
            PeerIdTool::channelLong(501558149),
            'userLong and channelLong must encode distinct canonical longs',
        );
    }

    public function test_message_model_has_no_peer_relation_methods(): void
    {
        // The mirror keeps peer refs as scalar peer-long columns (no Eloquent
        // belongsTo/hasMany); peer_id must not become a relation method.
        self::assertFalse(method_exists(TfMessage::class, 'peerId'));
        self::assertFalse(method_exists(TfMessage::class, 'fromUser'));
    }
}
