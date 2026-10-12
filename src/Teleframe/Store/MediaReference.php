<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store;

use InvalidArgumentException;

/**
 * MediaReference (Cycle 14, verbatim 2026-09-24 "we just pass the hash
 * telegram gives"): a Telegram file_reference promoted to a first-class
 * value object. A file that already lives in Telegram is addressable by
 * (id, access_hash, file_reference) alone — no download, no re-upload,
 * no local copy. This is what lets the hash row in tf_store_media stand in
 * for the bytes: "we delete something in main laravel storage but its hash
 * in telegram is still in database and the file is in the channel".
 *
 * The object is deliberately dumb and total: it never touches a scope, a
 * channel, or the filesystem, it only normalizes and validates. Every
 * refusal is an InvalidArgumentException (the caller passed something that
 * cannot address the file) — never a silent degradation into a re-upload,
 * which would reintroduce exactly the cost this feature removes.
 *
 * `media_type` is a MediaType, not a string (Cycle 25 landmine). It is the
 * one field that decides WHICH MTProto constructor goes on the wire, and a
 * photo's constructor is a different type with a different `id` type than a
 * document's — so an unmappable value must be impossible to hold, and an
 * existing row carrying one is refused at read by name rather than coerced.
 *
 * Zero-regex: string/type functions only.
 */
final class MediaReference
{
    public const DEFAULT_MIME = 'application/octet-stream';

    private function __construct(
        private readonly int $docId,
        private readonly int $accessHash,
        private readonly string $fileReference,
        private readonly int $size,
        private readonly string $mime,
        private readonly MediaType $mediaType,
    ) {}

    /**
     * From a tf_store_media row (StoreIndexContract::findMedia shape).
     *
     * A row whose media_type is absent defaults to Document — an absent
     * column is an old row, and document is what every writer in this
     * package emits. A row whose media_type is PRESENT but unrecognised is
     * refused by MediaType::fromStored, naming the value: absent is history,
     * wrong is a defect, and only one of the two is safe to assume.
     *
     * @param  array<string, mixed>  $row
     */
    public static function fromIndexRow(array $row): self
    {
        $stored = $row['media_type'] ?? null;
        $mediaType = $stored === null || $stored === ''
            ? MediaType::Document
            : MediaType::fromStored((string) $stored);

        return self::build(
            $row['doc_id'] ?? null,
            $row['access_hash'] ?? null,
            $row['file_reference'] ?? null,
            $row['size'] ?? null,
            (string) ($row['mime'] ?? self::DEFAULT_MIME),
            $mediaType,
        );
    }

    /**
     * From a raw TL `document` or `photo` object as it arrives on the wire
     * (the shape `media.document` / `media.photo` carries in message rows).
     * Both declare id/access_hash/file_reference at the top level, so one
     * builder serves both; anything else is named and refused.
     *
     * @param  array<string, mixed>  $document
     */
    public static function fromDocument(array $document): self
    {
        $type = (string) ($document['_'] ?? '');
        $mediaType = MediaType::fromWireType($type);

        return self::build(
            $document['id'] ?? null,
            $document['access_hash'] ?? null,
            $document['file_reference'] ?? null,
            $document['size'] ?? 0,
            (string) ($document['mime_type'] ?? self::DEFAULT_MIME),
            $mediaType,
        );
    }

    public function docId(): int
    {
        return $this->docId;
    }

    public function accessHash(): int
    {
        return $this->accessHash;
    }

    public function fileReference(): string
    {
        return $this->fileReference;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function mime(): string
    {
        return $this->mime;
    }

    public function mediaType(): MediaType
    {
        return $this->mediaType;
    }

    /**
     * The TL location Telegram needs to address the bytes it already holds.
     * inputDocumentFileLocation is an InputFileLocation (upload.getFile) and
     * is NOT the `id` of a reference publish — see toInputMedia().
     *
     * @return array<string, mixed>
     */
    public function toInputDocumentFileLocation(): array
    {
        return [
            '_' => 'inputDocumentFileLocation',
            'id' => $this->docId,
            'access_hash' => $this->accessHash,
            'file_reference' => $this->fileReference,
            'thumb_size' => '',
        ];
    }

    /**
     * The InputMedia constructor a reference publish goes out as, with the
     * `id` field built at the type THIS media type requires. Both the outer
     * constructor and the inner one are decided by MediaType, so a photo can
     * only ever be published as inputMediaPhoto + inputPhoto and a document
     * only as inputMediaDocument + inputDocument — there is no path from a
     * photo reference to a document on the wire.
     *
     * @return array<string, mixed>
     */
    public function toInputMedia(): array
    {
        return [
            '_' => $this->mediaType->inputMediaConstructor(),
            'id' => [
                '_' => $this->mediaType->inputIdConstructor(),
                'id' => $this->docId,
                'access_hash' => $this->accessHash,
                'file_reference' => $this->fileReference,
            ],
        ];
    }

    private static function build(mixed $docId, mixed $accessHash, mixed $fileReference, mixed $size, string $mime, MediaType $mediaType): self
    {
        $id = is_numeric($docId) ? (int) $docId : 0;
        if ($id <= 0) {
            throw new InvalidArgumentException('media reference needs a positive doc_id');
        }
        $reference = is_string($fileReference) ? $fileReference : '';
        if ($reference === '') {
            throw new InvalidArgumentException('media reference needs a non-empty file_reference');
        }

        return new self(
            $id,
            is_numeric($accessHash) ? (int) $accessHash : 0,
            $reference,
            is_numeric($size) ? (int) $size : 0,
            $mime === '' ? self::DEFAULT_MIME : $mime,
            $mediaType,
        );
    }
}
