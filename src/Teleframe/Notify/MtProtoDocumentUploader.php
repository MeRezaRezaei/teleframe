<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * MtProtoDocumentUploader — the PRODUCTION `uploader` seam for the
 * MTProto user-scope notify sender.
 *
 * `UserScopeTransport::sendDocument()` throws when no uploader is bound,
 * because the long half of a notification is the rendered report and a
 * caption alone would be a silent loss. This class is the other half of
 * that promise: it turns a path on disk into an `inputMediaUploadedDocument`
 * over `upload.saveFilePart` / `upload.saveBigFilePart`, which is what
 * `messages.sendMedia` needs before a byte of the report reaches a human.
 *
 * The part math is NOT invented here: it is the `TelegramVault::uploadBytes()`
 * loop (512 KB parts, the big-file switch above 10 MB, one `file_id` per
 * upload) lifted verbatim, so the vault's proven upload bookkeeping and the
 * notify module's cannot drift apart silently. ONE deliberate difference:
 * the big-file part count goes out as `file_total_parts`, which is the key
 * `upload.saveBigFilePart#de7b673d` actually declares in
 * `src/Core/MTProto/TL/TLRegistry.php`. The vault's older `total_parts` key
 * is left untouched there — that file is not this module's to edit, and the
 * registry is the authority on the wire.
 *
 * Every refusal is LOUD. A missing path, a directory, an unreadable file
 * and an empty file all throw `NotifyException` naming the path and the
 * reason, because a notify module that quietly skips an attachment is a
 * notify module that has stopped reporting. A failed part upload is logged
 * at error and ALWAYS rethrown (AGENTS.md: logging never swallows); the
 * notify queue's retry/dead-letter owns recovery.
 *
 * Plain-PHP constructible: the constructor stores a `LazyUserScope` and a
 * logger and touches nothing. It does not resolve a scope, does not read
 * a file and does not dial — the first byte goes out only when `__invoke`
 * runs, because the scope it borrows is itself lazy.
 *
 * HONEST LIMIT: the tests drive a recording double and assert the wire keys
 * the TL registry declares. No test here opens a socket; this upload path
 * has never been exercised against a live Telegram user session, and only
 * one can confirm that Telegram accepts the assembled media.
 */
final class MtProtoDocumentUploader
{
    /** 512 KB — the standard MTProto part size (TelegramVault's PART_SIZE). */
    public const PART_SIZE = 524288;

    /** Above 10 MB the upload switches to saveBigFilePart (TelegramVault's BIG_FILE_THRESHOLD). */
    public const BIG_FILE_THRESHOLD = 10485760;

    public function __construct(
        private readonly LazyUserScope $scope,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * @param  string  $path  the rendered report on disk
     * @param  string  $mime  its MIME type, carried through to the media
     * @param  string  $name  the file name Telegram will show
     * @return array<string, mixed> the inputMediaUploadedDocument for messages.sendMedia
     *
     * @throws NotifyException on an unusable path, or when a part upload fails
     */
    public function __invoke(string $path, string $mime, string $name): array
    {
        $bytes = $this->read($path);

        $size = strlen($bytes);
        $totalParts = max(1, (int) ceil($size / self::PART_SIZE));
        $isBig = $size > self::BIG_FILE_THRESHOLD;
        $fileId = random_int(1, PHP_INT_MAX);

        for ($part = 0; $part < $totalParts; $part++) {
            $params = [
                'file_id' => $fileId,
                'file_part' => $part,
                'bytes' => substr($bytes, $part * self::PART_SIZE, self::PART_SIZE),
            ];

            if ($isBig) {
                $params['file_total_parts'] = $totalParts;
                $this->sendPart('upload.saveBigFilePart', $params);
            } else {
                $this->sendPart('upload.saveFilePart', $params);
            }
        }

        return [
            '_' => 'inputMediaUploadedDocument',
            'file' => [
                '_' => 'inputFile',
                'id' => $fileId,
                'parts' => $totalParts,
                'name' => $name,
                'md5_checksum' => md5($bytes),
            ],
            'mime_type' => $mime,
            'attributes' => [
                ['_' => 'documentAttributeFilename', 'file_name' => $name],
            ],
        ];
    }

    /**
     * The whole file, or a loud refusal naming the path and the reason.
     *
     * @throws NotifyException
     */
    private function read(string $path): string
    {
        if ($path === '' || ! file_exists($path)) {
            throw new NotifyException(sprintf(
                'teleframe.notify: cannot upload the attachment — no such file: %s. Refusing rather than sending a notification that silently lost its report.',
                $path === '' ? '(empty path)' : $path,
            ));
        }

        if (! is_file($path)) {
            throw new NotifyException(sprintf(
                'teleframe.notify: cannot upload the attachment — not a regular file: %s. Refusing rather than sending a notification that silently lost its report.',
                $path,
            ));
        }

        if (! is_readable($path)) {
            throw new NotifyException(sprintf(
                'teleframe.notify: cannot upload the attachment — the file is not readable: %s. Refusing rather than sending a notification that silently lost its report.',
                $path,
            ));
        }

        $bytes = file_get_contents($path);

        if (! is_string($bytes)) {
            $this->logger?->error('teleframe.notify: reading the attachment for upload failed', ['path' => $path]);

            throw new NotifyException(sprintf(
                'teleframe.notify: cannot upload the attachment — the read of %s did not return bytes. Refusing rather than sending a notification that silently lost its report.',
                $path,
            ));
        }

        if ($bytes === '') {
            throw new NotifyException(sprintf(
                'teleframe.notify: cannot upload the attachment — the file is empty: %s. Refusing rather than sending an empty report to a human.',
                $path,
            ));
        }

        return $bytes;
    }

    /**
     * One part through the shared lazy scope. Failures are logged at error
     * and ALWAYS rethrown: a half-uploaded file is not a notification, and
     * pretending it succeeded is exactly the silent loss this module exists
     * to prevent.
     *
     * @param  array<string, mixed>  $params
     *
     * @throws NotifyException when the scope cannot be resolved
     * @throws Throwable whatever the scope raised for that part
     */
    private function sendPart(string $method, array $params): void
    {
        try {
            $this->scope->scope()->call($method, $params);
        } catch (NotifyException $e) {
            $this->logger?->error('teleframe.notify: the MTProto attachment upload could not start', [
                'method' => $method,
                'file_part' => $params['file_part'] ?? null,
                'exception' => $e,
            ]);

            throw $e;
        } catch (Throwable $e) {
            $this->logger?->error('teleframe.notify: an MTProto attachment part upload failed', [
                'method' => $method,
                'file_part' => $params['file_part'] ?? null,
                'exception' => $e,
            ]);

            throw $e;
        }
    }
}
