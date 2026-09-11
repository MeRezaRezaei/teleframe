<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScope;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerIdTool;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfChannelParticipant;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfUser;

/**
 * Relationship/shape assertions against the tf_* domain models derived from
 * TL_telegram_v227.tl. Locks the domain relation surface: messages belong to
 * their author/channel, channels have participants, participants belong to
 * users/channels, every model boots the account scope.
 */
final class RelationGenerationTest extends TestCase
{
    public function test_message_instance_has_belongsTo_for_object_refs(): void
    {
        $message = new TfMessage();

        // Seed the FK attribute so BelongsTo construction does not recurse
        // into the `fromUser` attribute/relation lookup on a fresh unsaved model.
        $message->from_id = 777;

        $from = $message->fromUser();

        self::assertInstanceOf(BelongsTo::class, $from);
        self::assertInstanceOf(TfUser::class, $from->getRelated());

        // A scalar/nat column must NOT become a relation method.
        self::assertFalse(method_exists(TfMessage::class, 'peerId'), 'peer_id is a scalar peer-long, not a belongsTo relation');
    }

    public function test_anchor_has_reverse_hasMany_for_incoming_refs(): void
    {
        $found = false;

        foreach (get_class_methods(TfUser::class) as $method) {
            $relation = (new TfUser())->{$method}();

            if (! $relation instanceof HasMany) {
                continue;
            }

            $related = $relation->getRelated();

            if ($related instanceof TfMessage) {
                $found = true;
                break;
            }
        }

        self::assertTrue($found, 'TfUser must expose a hasMany whose related model is TfMessage');
    }

    public function test_channel_has_participants_has_many(): void
    {
        $relation = (new TfChannel())->participants();

        self::assertInstanceOf(HasMany::class, $relation);
        self::assertInstanceOf(TfChannelParticipant::class, $relation->getRelated());
    }

    public function test_every_generated_model_boots_account_scope(): void
    {
        $classes = [
            TfUser::class,
            TfMessage::class,
            TfChannel::class,
            TfChannelParticipant::class,
        ];

        foreach ($classes as $class) {
            /** @var TfModel $model */
            $model = new $class();
            $scopes = $model->getGlobalScopes();
            self::assertArrayHasKey(AccountScope::class, $scopes, "{$class} must boot the global AccountScope");
        }
    }

    public function test_peer_ref_models_use_peer_resolution_trait(): void
    {
        self::assertContains(PeerResolution::class, class_uses_recursive(TfMessage::class));
    }

    public function test_peer_resolution_scopes_filter_canonical_long(): void
    {
        self::assertTrue(method_exists(TfMessage::class, 'scopeWherePeerIsChannel'));
        self::assertTrue(method_exists(TfMessage::class, 'scopeWherePeerIsUser'));
        self::assertTrue(method_exists(TfMessage::class, 'scopeWherePeerIsChat'));

        // Canonical long separation: a user id and a channel id must never collide.
        self::assertNotSame(
            PeerIdTool::userLong(501558149),
            PeerIdTool::channelLong(501558149),
            'userLong and channelLong must encode distinct canonical longs',
        );
    }
}