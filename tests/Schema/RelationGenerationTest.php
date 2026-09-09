<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScope;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerIdTool;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMedia;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessageEntities;

/**
 * Relationship/shape assertions against the GENERATED models directly — no
 * migrations/schema needed. Locks the generated relation surface described in
 * plan 2026-09-09-telegram-id-mirror-and-relations.md (Task 2).
 *
 * N.B. extends TestCase (Testbench) rather than a plain PHPUnit TestCase
 * because constructing Eloquent relations (belongsTo/hasMany) resolves the
 * model's database connection at object-build time; a booted container +
 * default connection is required even though no queries are executed.
 */
final class RelationGenerationTest extends TestCase
{
    public function test_message_instance_has_belongsTo_for_object_refs(): void
    {
        $message = new TlMessageMessage();

        // Seed the FK attribute so BelongsTo construction does not recurse
        // into the `media` attribute/relation lookup on a fresh unsaved model.
        $message->media = 'seed-fk-value';

        $media = $message->media();

        self::assertInstanceOf(BelongsTo::class, $media);
        self::assertInstanceOf(TlMessageMedia::class, $media->getRelated());

        // A scalar/nat column must NOT become a relation method.
        self::assertFalse(method_exists(TlMessageMessage::class, 'peerId'), 'peer_id is a scalar peer-long, not a belongsTo relation');
    }

    public function test_anchor_has_reverse_hasMany_for_incoming_refs(): void
    {
        $found = false;

        foreach (get_class_methods(TlMessageMedia::class) as $method) {
            $relation = (new TlMessageMedia())->{$method}();

            if (! $relation instanceof HasMany) {
                continue;
            }

            $related = $relation->getRelated();

            if ($related instanceof TlMessageMessage) {
                $found = true;
                break;
            }
        }

        self::assertTrue($found, 'TlMessageMedia must expose a reverse hasMany whose related model is TlMessageMessage');
    }

    public function test_vector_param_yields_hasMany_to_child_model(): void
    {
        $relation = (new TlMessageMessage())->entities();

        self::assertInstanceOf(HasMany::class, $relation);
        self::assertSame('tl_message_message__entities', $relation->getRelated()->getTable());
    }

    public function test_every_generated_model_boots_account_scope(): void
    {
        $classes = [
            TlMessage::class,
            TlMessageMessage::class,
            TlMessageMessageEntities::class,
        ];

        foreach ($classes as $class) {
            $scopes = (new $class())->getGlobalScopes();
            self::assertArrayHasKey(AccountScope::class, $scopes, "{$class} must boot the global AccountScope");
        }
    }

    public function test_peer_ref_models_use_peer_resolution_trait(): void
    {
        self::assertContains(PeerResolution::class, class_uses_recursive(TlMessageMessage::class));
    }

    public function test_peer_resolution_scopes_filter_canonical_long(): void
    {
        self::assertTrue(method_exists(TlMessageMessage::class, 'scopeWherePeerIsChannel'));
        self::assertTrue(method_exists(TlMessageMessage::class, 'scopeWherePeerIsUser'));
        self::assertTrue(method_exists(TlMessageMessage::class, 'scopeWherePeerIsChat'));

        // Canonical long separation: a user id and a channel id must never collide.
        self::assertNotSame(
            PeerIdTool::userLong(501558149),
            PeerIdTool::channelLong(501558149),
            'userLong and channelLong must encode distinct canonical longs',
        );
    }
}
