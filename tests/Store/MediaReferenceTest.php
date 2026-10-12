<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Store\MediaReference;
use MeRezaRezaei\Teleframe\Store\MediaType;
use PHPUnit\Framework\TestCase;

/**
 * MediaReference (Cycle 14, "we just pass the hash telegram gives"):
 * the value object that turns a tf_store_media row (or a raw TL document)
 * into the inputDocumentFileLocation Telegram accepts — the whole point is
 * that no bytes are read to do it. Every refusal is loud: a reference with
 * no file_reference or a non-positive doc_id cannot address anything, so it
 * must never degrade into a silent re-upload.
 */
final class MediaReferenceTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function indexRow(): array
    {
        return [
            'media_type' => 'document',
            'doc_id' => 777,
            'access_hash' => 123456789,
            'file_reference' => 'AQAD-file-reference-bytes',
            'size' => 4096,
            'mime' => 'image/jpeg',
            'content_sha256' => null,
            'indexed_at_unix' => 1758000000,
        ];
    }

    public function test_from_index_row_exposes_the_telegram_identity(): void
    {
        $ref = MediaReference::fromIndexRow(self::indexRow());

        self::assertSame(777, $ref->docId());
        self::assertSame(123456789, $ref->accessHash());
        self::assertSame('AQAD-file-reference-bytes', $ref->fileReference());
        self::assertSame(4096, $ref->size());
        self::assertSame('image/jpeg', $ref->mime());
        self::assertSame(MediaType::Document, $ref->mediaType());
    }

    public function test_to_input_document_file_location_is_the_telegram_shape(): void
    {
        $location = MediaReference::fromIndexRow(self::indexRow())->toInputDocumentFileLocation();

        self::assertSame('inputDocumentFileLocation', $location['_']);
        self::assertSame(777, $location['id']);
        self::assertSame(123456789, $location['access_hash']);
        self::assertSame('AQAD-file-reference-bytes', $location['file_reference']);
        self::assertSame('', $location['thumb_size']);
    }

    public function test_from_raw_document_row_builds_the_same_reference(): void
    {
        $document = [
            '_' => 'document',
            'id' => 777,
            'access_hash' => 123456789,
            'file_reference' => 'AQAD-file-reference-bytes',
            'size' => 4096,
            'mime_type' => 'image/jpeg',
        ];

        $ref = MediaReference::fromDocument($document);

        self::assertSame(777, $ref->docId());
        self::assertSame('AQAD-file-reference-bytes', $ref->fileReference());
        self::assertSame(4096, $ref->size());
        self::assertSame('image/jpeg', $ref->mime());
        self::assertSame(MediaType::Document, $ref->mediaType());
        self::assertSame(
            MediaReference::fromIndexRow(self::indexRow())->toInputDocumentFileLocation(),
            $ref->toInputDocumentFileLocation(),
        );
    }

    public function test_empty_file_reference_is_refused(): void
    {
        $row = self::indexRow();
        $row['file_reference'] = '';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('file_reference');
        MediaReference::fromIndexRow($row);
    }

    public function test_non_positive_doc_id_is_refused(): void
    {
        $row = self::indexRow();
        $row['doc_id'] = 0;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('doc_id');
        MediaReference::fromIndexRow($row);
    }

    public function test_non_media_wire_object_is_refused(): void
    {
        // A photo IS a media type now (MediaType::Photo), so this must name a
        // type that is not one at all — and the whole point is that an
        // unnameable constructor is refused by name instead of being read as a
        // document.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('webpage');
        MediaReference::fromDocument(['_' => 'webpage', 'id' => 5]);
    }

    public function test_photo_object_without_a_file_reference_is_refused(): void
    {
        // photo#fb197a65 declares file_reference at the top level, so a photo
        // that has none cannot address anything — and must not degrade into a
        // silent re-upload.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('file_reference');
        MediaReference::fromDocument(['_' => 'photo', 'id' => 5]);
    }

    public function test_missing_size_and_mime_fall_back_to_safe_defaults(): void
    {
        $ref = MediaReference::fromIndexRow([
            'doc_id' => 7,
            'access_hash' => 8,
            'file_reference' => 'ref',
        ]);

        self::assertSame(0, $ref->size());
        self::assertSame('application/octet-stream', $ref->mime());
        self::assertSame(MediaType::Document, $ref->mediaType());
    }
}
