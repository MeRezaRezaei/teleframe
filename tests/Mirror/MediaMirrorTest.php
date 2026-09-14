<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Mirror;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Mirror\Models\TfDocument;
use MeRezaRezaei\Teleframe\Mirror\Models\TfPhoto;
use MeRezaRezaei\Teleframe\Mirror\Models\TfStickerSet;
use MeRezaRezaei\Teleframe\Mirror\Models\TfWebPage;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountContext;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

final class MediaMirrorTest extends TestbenchTestCase
{
    private const MEDIA_TABLES = [
        'tf_documents',
        'tf_documents_attributes',
        'tf_documents_thumbs',
        'tf_documents_video_thumbs',
        'tf_photos',
        'tf_photos_sizes',
        'tf_photos_video_sizes',
        'tf_web_pages',
        'tf_web_pages_url',
        'tf_sticker_sets',
        'tf_sticker_sets_installed_date',
        'tf_sticker_sets_thumbs',
        'tf_sticker_sets_thumb_dc_id',
        'tf_sticker_sets_thumb_version',
        'tf_sticker_sets_thumb_document_id',
    ];

    private MirrorFactWriter $writer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->writer = new MirrorFactWriter;
    }

    protected function tearDown(): void
    {
        AccountContext::reset();
        parent::tearDown();
    }

    protected function getApplicationBasePath()
    {
        return dirname(__DIR__, 2);
    }

    protected function getPackageProviders($app): array
    {
        return [];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    private function tempMediaDial(): string
    {
        $out = sys_get_temp_dir().'/tfmedia_'.uniqid();
        @mkdir($out, 0777, true);

        foreach (glob(dirname(__DIR__, 2).'/src/Laravel/Migrations/2026_09_14_2000*.php') ?: [] as $file) {
            copy($file, $out.'/'.basename($file));
        }

        return $out;
    }

    private function migrateMedia(): void
    {
        $dial = $this->tempMediaDial();
        try {
            $this->artisan('migrate', ['--path' => $dial, '--realpath' => true])->assertExitCode(0);
        } finally {
            $this->rrmdir($dial);
        }
    }

    public function test_media_dial_migrates_into_the_nf5_surface(): void
    {
        $this->migrateMedia();

        foreach (self::MEDIA_TABLES as $table) {
            self::assertTrue(Schema::hasTable($table), "{$table} must exist after the media dial migrates");
        }

        self::assertTrue(Schema::hasColumns('tf_documents', ['account_id', 'constructor', 'id', 'access_hash', 'file_reference', 'date', 'mime_type', 'size', 'dc_id']));
        self::assertTrue(Schema::hasColumns('tf_documents_attributes', ['account_id', 'id', 'position', 'constructor', 'w', 'h', 'duration', 'file_name', 'mask', 'round_message']));
        self::assertTrue(Schema::hasColumns('tf_photos', ['account_id', 'constructor', 'id', 'access_hash', 'file_reference', 'date', 'dc_id', 'has_stickers']));
        self::assertTrue(Schema::hasColumns('tf_web_pages', ['account_id', 'constructor', 'id', 'date']));
        self::assertTrue(Schema::hasColumns('tf_web_pages_url', ['account_id', 'id', 'constructor', 'url']));
        self::assertTrue(Schema::hasColumns('tf_sticker_sets', ['account_id', 'constructor', 'id', 'access_hash', 'title', 'short_name', 'count', 'hash', 'archived', 'official', 'masks', 'emojis', 'text_color', 'channel_emoji_status', 'creator']));
        self::assertTrue(Schema::hasColumns('tf_sticker_sets_installed_date', ['account_id', 'id', 'constructor', 'installed_date']));
    }

    public function test_composite_parent_key_rejects_duplicates(): void
    {
        $this->migrateMedia();

        $row = [
            'account_id' => 42,
            'constructor' => 'document',
            'id' => 7001,
            'access_hash' => 883745,
            'file_reference' => 'aa00bb11',
            'date' => 1726000000,
            'mime_type' => 'image/png',
            'size' => 12345,
            'dc_id' => 2,
        ];

        DB::table('tf_documents')->insert($row);

        $this->expectException(QueryException::class);
        DB::table('tf_documents')->insert($row);
    }

    public function test_parent_first_document_ingest_inserts_cleanly(): void
    {
        $this->migrateMedia();

        $rows = [
            [
                'table' => 'tf_documents',
                'row' => [
                    'account_id' => 42,
                    'constructor' => 'document',
                    'id' => 7002,
                    'access_hash' => 111,
                    'file_reference' => 'feedbeef',
                    'date' => 1726000001,
                    'mime_type' => 'video/mp4',
                    'size' => 987654,
                    'dc_id' => 2,
                ],
            ],
            [
                'table' => 'tf_documents_attributes',
                'row' => [
                    'account_id' => 42,
                    'id' => 7002,
                    'position' => 0,
                    'constructor' => 'documentAttributeVideo',
                    'round_message' => 1,
                    'supports_streaming' => 1,
                    'duration' => 4,
                    'w' => 1280,
                    'h' => 720,
                    'video_codec' => 'h264',
                ],
            ],
            [
                'table' => 'tf_documents_thumbs',
                'row' => [
                    'account_id' => 42,
                    'id' => 7002,
                    'position' => 0,
                    'constructor' => 'photoSize',
                    'type' => 'm',
                    'w' => 320,
                    'h' => 180,
                    'size' => 2048,
                ],
            ],
            [
                'table' => 'tf_documents_thumbs',
                'row' => [
                    'account_id' => 42,
                    'id' => 7002,
                    'position' => 1,
                    'constructor' => 'photoStrippedSize',
                    'type' => 's',
                    'bytes' => 'dead',
                ],
            ],
            [
                'table' => 'tf_documents_video_thumbs',
                'row' => [
                    'account_id' => 42,
                    'id' => 7002,
                    'position' => 0,
                    'constructor' => 'videoSize',
                    'type' => 't',
                    'w' => 50,
                    'h' => 50,
                    'size' => 1024,
                    'video_start_ts' => 1,
                ],
            ],
        ];

        $write = $this->writer->write(DB::connection(), $rows);

        self::assertSame(count($rows), $write['inserted'], 'parent-first media rows must all insert');
        self::assertEmpty($write['fkClues']);

        self::assertSame(1, DB::table('tf_documents')->where('account_id', 42)->where('id', 7002)->count());
        self::assertSame('feedbeef', DB::table('tf_documents')->where('id', 7002)->value('file_reference'));
        self::assertSame(['m', 's'], DB::table('tf_documents_thumbs')->where('account_id', 42)->where('id', 7002)->orderBy('position')->pluck('type')->all());
        self::assertSame('photoSize', DB::table('tf_documents_thumbs')->where('position', 0)->value('constructor'));
        self::assertSame(1, DB::table('tf_documents_attributes')->where('id', 7002)->where('position', 0)->value('round_message'));
        self::assertSame(1024, DB::table('tf_documents_video_thumbs')->where('id', 7002)->where('position', 0)->value('size'));
    }

    public function test_child_before_parent_document_is_an_fk_clue(): void
    {
        $this->migrateMedia();

        $orphan = [
            'table' => 'tf_documents_thumbs',
            'row' => [
                'account_id' => 42,
                'id' => 9999,
                'position' => 0,
                'constructor' => 'photoSize',
                'type' => 'm',
                'w' => 10,
                'h' => 10,
            ],
        ];

        $write = $this->writer->write(DB::connection(), [$orphan]);

        self::assertSame(0, $write['inserted'], 'orphan child must not insert');
        self::assertCount(1, $write['fkClues']);
        self::assertStringContainsString('tf_documents_thumbs', $write['fkClues'][0]);
        self::assertStringContainsString('FOREIGN KEY', $write['fkClues'][0]);
    }

    public function test_missing_required_fact_data_surfaces_a_not_null_clue(): void
    {
        $this->migrateMedia();

        $incomplete = [
            'table' => 'tf_documents',
            'row' => [
                'account_id' => 42,
                'constructor' => 'document',
                'id' => 7003,
            ],
        ];

        $write = $this->writer->write(DB::connection(), [$incomplete]);

        self::assertSame(0, $write['inserted']);
        self::assertCount(1, $write['fkClues']);
        self::assertStringContainsString('NOT NULL', $write['fkClues'][0]);
    }

    public function test_hex_file_reference_persists_verbatim(): void
    {
        $this->migrateMedia();
        AccountContext::set(42);

        TfPhoto::create([
            'account_id' => 42,
            'constructor' => 'photo',
            'id' => 8001,
            'access_hash' => 555,
            'file_reference' => '00dead00beef',
            'date' => 1726000002,
            'dc_id' => 2,
            'has_stickers' => true,
        ]);

        $photo = TfPhoto::where('id', 8001)->firstOrFail();

        self::assertSame('00dead00beef', $photo->file_reference);
        self::assertSame(555, $photo->access_hash);
        self::assertTrue($photo->has_stickers);
        self::assertSame('00dead00beef', DB::table('tf_photos')->where('id', 8001)->value('file_reference'));
    }

    public function test_media_relations_populate_children(): void
    {
        $this->migrateMedia();
        AccountContext::set(42);

        $set = TfStickerSet::create([
            'account_id' => 42,
            'constructor' => 'stickerSet',
            'id' => 9001,
            'access_hash' => 777,
            'title' => 'Pack',
            'short_name' => 'pack',
            'count' => 12,
            'hash' => 1,
            'official' => true,
            'emojis' => false,
        ]);

        $set->thumbs()->create(['account_id' => 42, 'position' => 0, 'constructor' => 'photoSize', 'type' => 's', 'w' => 60, 'h' => 60, 'size' => 100]);
        $set->thumbs()->create(['account_id' => 42, 'position' => 1, 'constructor' => 'photoCachedSize', 'type' => 'm', 'w' => 120, 'h' => 120, 'bytes' => 'cafe']);
        $set->installedDate()->create(['account_id' => 42, 'installed_date' => 1726000003]);
        $set->thumbDocumentId()->create(['account_id' => 42, 'thumb_document_id' => 5501]);

        $page = TfWebPage::create(['account_id' => 42, 'constructor' => 'webPage', 'id' => 9501, 'date' => 1726000004]);
        $page->url()->create(['account_id' => 42, 'url' => 'https://example.com/x']);

        self::assertSame(2, $set->thumbs()->count());
        self::assertSame(['s', 'm'], $set->thumbs()->orderBy('position')->pluck('type')->all());
        self::assertSame(1726000003, $set->installedDate->installed_date);
        self::assertSame(5501, $set->thumbDocumentId->thumb_document_id);
        self::assertTrue($set->official);
        self::assertFalse($set->emojis);
        self::assertSame('https://example.com/x', $page->url->url);

        self::assertSame('Pack', $set->thumbs()->orderBy('position')->first()->stickerSet->title);
    }

    public function test_account_isolation_across_media_rows(): void
    {
        $this->migrateMedia();

        AccountContext::set(42);
        TfDocument::create([
            'account_id' => 42,
            'constructor' => 'document',
            'id' => 7004,
            'access_hash' => 1,
            'file_reference' => 'aa',
            'date' => 1726000005,
            'mime_type' => 'text/plain',
            'size' => 3,
            'dc_id' => 2,
        ]);

        AccountContext::set(7);
        TfDocument::create([
            'account_id' => 7,
            'constructor' => 'document',
            'id' => 7005,
            'access_hash' => 2,
            'file_reference' => 'bb',
            'date' => 1726000006,
            'mime_type' => 'text/plain',
            'size' => 4,
            'dc_id' => 3,
        ]);

        self::assertSame(1, TfDocument::all()->count(), 'global scope must isolate to the active account');
        self::assertSame(2, TfDocument::acrossAccounts()->count(), 'acrossAccounts must see every account');
        self::assertSame(1, TfDocument::forAccount(42)->count());
        self::assertSame(7004, TfDocument::forAccount(42)->value('id'));
    }

    public function test_parent_delete_cascades_to_media_children(): void
    {
        $this->migrateMedia();
        AccountContext::set(42);

        $doc = TfDocument::create([
            'account_id' => 42,
            'constructor' => 'document',
            'id' => 7006,
            'access_hash' => 9,
            'file_reference' => 'cc',
            'date' => 1726000007,
            'mime_type' => 'application/octet-stream',
            'size' => 10,
            'dc_id' => 2,
        ]);

        $doc->thumbs()->create(['account_id' => 42, 'position' => 0, 'constructor' => 'photoSize', 'type' => 's', 'w' => 1, 'h' => 1, 'size' => 1]);
        $doc->attributes()->create(['account_id' => 42, 'position' => 0, 'constructor' => 'documentAttributeFilename', 'file_name' => 'f.bin']);

        self::assertSame(1, DB::table('tf_documents_thumbs')->count());
        self::assertSame(1, DB::table('tf_documents_attributes')->count());

        $doc->delete();

        self::assertSame(0, DB::table('tf_documents')->count());
        self::assertSame(0, DB::table('tf_documents_thumbs')->count(), 'thumbs must cascade with the parent');
        self::assertSame(0, DB::table('tf_documents_attributes')->count(), 'attributes must cascade with the parent');
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() && ! $item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
