<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScope;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfChannelParticipant;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfChat;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfDialog;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfPhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStickerSet;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfUser;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfWallpaper;

/**
 * Verify that the NF5 mirror tf_* parent models (derived from
 * TL_telegram_v227.tl via the committed mirror catalog) are autoloadable,
 * instantiable, account-scoped, non-incrementing, and map to the correct
 * tables.
 */
final class GeneratedLoadTest extends TestCase
{
    /** @var array<class-string<TfMirrorModel>, string> model class => expected table */
    private const DOMAIN_MODELS = [
        TfUser::class => 'tf_users',
        TfChat::class => 'tf_chats',
        TfMessage::class => 'tf_messages',
        TfDialog::class => 'tf_dialogs',
        TfDocument::class => 'tf_documents',
        TfPhoto::class => 'tf_photos',
        TfStickerSet::class => 'tf_sticker_sets',
        TfWallpaper::class => 'tf_wallpapers',
        TfChannelParticipant::class => 'tf_channel_participants',
    ];

    public function test_domain_models_are_autoloadable_and_newable(): void
    {
        foreach (self::DOMAIN_MODELS as $class => $expectedTable) {
            self::assertTrue(class_exists($class), "Model $class must be autoloadable");

            $model = new $class;
            self::assertInstanceOf(TfMirrorModel::class, $model);
            self::assertSame($expectedTable, $model->getTable(), "Model $class must map to $expectedTable");
            self::assertFalse($model->getIncrementing(), "Mirror parent $class must have incrementing=false");
        }
    }

    public function test_domain_models_are_account_scoped(): void
    {
        foreach (array_keys(self::DOMAIN_MODELS) as $class) {
            $model = new $class;
            self::assertArrayHasKey(AccountScope::class, $model->getGlobalScopes(), "Mirror parent $class must boot the account scope");
        }
    }
}
