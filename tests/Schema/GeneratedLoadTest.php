<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfChannelParticipant;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfChat;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfDialog;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfPhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfStickerSet;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfStory;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfUser;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfWallpaper;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * Verify that all tf_* domain models (derived from TL_telegram_v227.tl)
 * are autoloadable, instantiable, and map to the correct tables.
 */
final class GeneratedLoadTest extends TestCase
{
    /** @var array<class-string<TfModel>, string> model class => expected table */
    private const DOMAIN_MODELS = [
        TfUser::class => 'tf_users',
        TfChat::class => 'tf_chats',
        TfChannel::class => 'tf_channels',
        TfMessage::class => 'tf_messages',
        TfDialog::class => 'tf_dialogs',
        TfDocument::class => 'tf_documents',
        TfPhoto::class => 'tf_photos',
        TfStickerSet::class => 'tf_sticker_sets',
        TfStory::class => 'tf_stories',
        TfWallpaper::class => 'tf_wallpapers',
        TfChannelParticipant::class => 'tf_channel_participants',
    ];

    /** Models that use a surrogate auto-increment PK (not composite). */
    private const SURROGATE_PK_MODELS = [
        TfMessage::class,
    ];

    public function test_domain_models_are_autoloadable_and_newable(): void
    {
        foreach (self::DOMAIN_MODELS as $class => $expectedTable) {
            self::assertTrue(class_exists($class), "Model $class must be autoloadable");

            $model = new $class();
            self::assertInstanceOf(TfModel::class, $model);
            self::assertSame($expectedTable, $model->getTable(), "Model $class must map to $expectedTable");

            if (in_array($class, self::SURROGATE_PK_MODELS, true)) {
                self::assertTrue($model->getIncrementing(), "Model $class uses surrogate PK (incrementing=true)");
            } else {
                self::assertFalse($model->getIncrementing(), "Model $class must have incrementing=false (composite PK)");
            }
        }
    }

    public function test_user_model_has_correct_primary_key(): void
    {
        $user = new TfUser();
        self::assertSame('id', $user->getKeyName());
        self::assertFalse($user->getIncrementing());
    }

    public function test_dialog_model_has_composite_primary_key(): void
    {
        $dialog = new TfDialog();
        self::assertSame('peer_id', $dialog->getKeyName());
        self::assertFalse($dialog->getIncrementing());
    }

    public function test_message_model_uses_surrogate_pk(): void
    {
        $message = new TfMessage();
        self::assertSame('id', $message->getKeyName());
        self::assertTrue($message->getIncrementing(), 'Messages use bigIncrements surrogate PK');
    }

    public function test_story_model_has_composite_primary_key(): void
    {
        $story = new TfStory();
        self::assertSame('story_id', $story->getKeyName());
        self::assertFalse($story->getIncrementing());
    }
}
