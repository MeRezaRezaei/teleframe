<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use MeRezaRezaei\Teleframe\Core\Passport\PassportDecryptor;

/**
 * PassportDocumentResolver — THE PRODUCTION NESTED-DECRYPT PATH.
 *
 * Before this class, `PassportDecryptor::decryptDataCredentials()` and
 * `decryptFileCredentials()` were shipped, spec-conformant, and DEAD: nothing
 * in `src/` ever invoked either of them. `SecureValueTraversal` walked the
 * whole §7 `SecureValue` field list and then deliberately only COUNTED the
 * nested `SecureData` and the files, and `PassportCredentialsVerifier` said so
 * in its own docblock — `assertDataCredentials()` and `assertFileCredentials()`
 * were written, reviewed, and unreachable. A document arriving in a passport
 * response was counted, listed in `fields_verified`, and then never opened.
 *
 * This class is what makes those three dead methods live, and it does so
 * WITHOUT weakening either of them: the transport check runs immediately
 * before the decrypt, both are called with the raw bytes
 * `PassportDocumentSource` resolved, and a refusal from either is converted
 * into a {@see PassportDocumentRefusal} rather than an exception — because the
 * host asked for eleven document types and the user supplied four, and the
 * right answer is to log in on the four and REPORT the seven, not to abort.
 *
 * ══ THE RULE THIS CLASS EXISTS TO ENFORCE ══════════════════════════════════
 *
 *   > **every `allowed_types` value produces a document OR A NAMED REFUSAL.
 *   > Never a silent absence.**
 *
 * Every public method here returns `PassportDocument|PassportDocumentRefusal`
 * and NONE of them returns null. There is no "nothing found" path, because
 * "nothing found" is the defect: it is what made a `driver_license` request
 * indistinguishable from a login that worked perfectly. A refusal is a value
 * that can be counted, aggregated, logged, reported to Telegram and asserted
 * in a test; an absence is none of those.
 *
 * ══ THE ENCRYPTION SPLIT IS NOT THIS CLASS'S BUSINESS ════════════════════
 *
 * `PassportDocumentSource` decided, once, whether a field arrived base64 or
 * raw. This class therefore receives raw 32-byte digests and raw ciphertext
 * and passes them straight to `PassportDecryptor`, which refuses anything that
 * is not 32 raw bytes at its own boundary. If this class ever tried to be
 * clever about encodings it would reintroduce the always-false `hash_equals`
 * — so it does not, and it never calls `base64_decode` on a digest.
 *
 * ══ THE DOWNLOAD, AND THE THREE WAYS IT REFUSES ══════════════════════════
 *
 * `upload.getFile` (NOT `help.getFile`, which is not a Telegram method —
 * `PassportFileEnvelope` carries the full citation) in chunks, per file. Three
 * distinct refusals, because a host acts on them differently:
 *
 *   - {@see PassportDocumentRefusal::FILE_HASH_UNAVAILABLE} — the shape has no
 *     `file_hash`/`secret`. Real on the Bot API, where `PassportFile` carries
 *     only `file_id`/`file_unique_id`/`file_size`/`file_date`. The bytes could
 *     be downloaded but nothing on earth could decrypt them;
 *   - {@see PassportDocumentRefusal::WIRE_UNAVAILABLE} — no
 *     `PassportWireContract` is bound, so no dispatch is possible. This is a
 *     CONFIGURATION fact the host can fix in one line;
 *   - {@see PassportDocumentRefusal::DOWNLOAD_FAILED} — Telegram refused, or
 *     returned no bytes, or asked for a CDN redirect this seam does not
 *     implement. Naming it is what stops "CDN redirect" from becoming another
 *     silent absence.
 *
 * §4's "max 10 MB" is checked BEFORE download where the file declares its size,
 * and after download where it does not, so an oversized file is refused rather
 * than buffered whole on the way to being refused.
 *
 * SECURITY: no logger, no exception, no return value that carries a plaintext
 * anywhere but a {@see PassportDocument}. Nothing here ever emits the
 * ciphertext, the digest, the secret or the `access_hash`.
 *
 * Plain-PHP constructible (standalone-smoke): two constructor arguments, both
 * optional, no container, no config, no filesystem.
 * Zero regex (`src/Teleframe`): no `preg_*`, only `strlen`/`json_decode`.
 */
final class PassportDocumentResolver
{
    /**
     * @param  string|null  $privateKeyPem  the service RSA private key; null
     *                                      means no document can be decrypted
     *                                      and every `data` slot refuses by name
     * @param  PassportWireContract|null  $wire  the download seam; null means
     *                                           every file slot refuses with
     *                                           {@see PassportDocumentRefusal::WIRE_UNAVAILABLE}
     */
    public function __construct(
        private readonly ?string $privateKeyPem = null,
        private readonly ?PassportWireContract $wire = null,
    ) {}

    /**
     * A copy of this resolver carrying the service key.
     *
     * The key lives in `PassportKeyLoader` and is materialised only at the
     * moment a chain runs, so a host can inject one shared resolver without
     * this package resolving key material at construction time. The seam the
     * resolver was built with is preserved, and a resolver that already has a
     * key keeps it — this can only ever ADD the key, never replace it.
     */
    public function withPrivateKey(string $privateKeyPem): self
    {
        if ($this->privateKeyPem !== null && $this->privateKeyPem !== '') {
            return $this;
        }

        return new self($privateKeyPem, $this->wire);
    }

    /**
     * The §4 `data` slot: `PassportCredentialsVerifier::assertDataCredentials()`
     * (transport) then `PassportDecryptor::decryptDataCredentials()` (crypto),
     * both on raw bytes, in that order — the check before the AES, so a
     * tampered document is refused before anything decrypts it.
     */
    public function resolveData(string $type, PassportDocumentSource $source): PassportDocument|PassportDocumentRefusal
    {
        $slot = PassportDocumentRefusal::SLOT_DATA;

        if (! in_array($slot, $source->allowedSlots($type), true)) {
            // §7 grants this type no `data` field. The five file-only types
            // land here, and so does a name outside the table entirely.
            return PassportDocumentRefusal::named(
                $type,
                $slot,
                PassportDocumentRefusal::SLOT_ABSENT,
                'the SecureValueType/Allowed fields table grants this type no data field; '
                .'it carries files, not an encrypted payload.',
            );
        }

        if (! $source->hasChannelFor($type)) {
            return PassportDocumentRefusal::named(
                $type,
                $slot,
                PassportDocumentRefusal::ABSENT,
                'this update carried no passport document channel at all.',
            );
        }

        if (! $source->hasType($type)) {
            return PassportDocumentRefusal::named(
                $type,
                $slot,
                PassportDocumentRefusal::ABSENT,
                'Telegram sent no element of this type in this response.',
            );
        }

        $fault = $source->encodingFault($type);
        if ($fault !== '') {
            return PassportDocumentRefusal::named($type, $slot, $fault, $this->faultDetail($fault));
        }

        $envelope = $source->dataEnvelope($type);
        if ($envelope === null) {
            return PassportDocumentRefusal::named(
                $type,
                $slot,
                PassportDocumentRefusal::CIPHERTEXT_ABSENT,
                'the element carried no encrypted data payload to decrypt.',
            );
        }

        $pem = $this->privateKeyPem;
        if ($pem === null || $pem === '') {
            return PassportDocumentRefusal::named(
                $type,
                $slot,
                PassportDocumentRefusal::WIRE_UNAVAILABLE,
                'no service private key is bound, so no document can be decrypted.',
            );
        }

        $payloadClass = PassportDocument::DATA_PAYLOAD_CLASSES[$type] ?? PassportDocument::CLASS_ID_DOCUMENT;

        try {
            // The transport check first, and independently: `PassportDecryptor`
            // raises the same `hash_equals` internally, so this is a second,
            // explicitly-named assertion rather than a new security boundary.
            PassportCredentialsVerifier::assertDataCredentials(
                $envelope->ciphertext(),
                $envelope->rawHash(),
                $envelope->rawSecret(),
                $pem,
            );

            $plaintext = $envelope->decryptWith($pem);
        } catch (\RuntimeException) {
            // ONE refusal for the whole §4 crypto chain — a tampered document
            // and a mis-encoded one must not be distinguishable from outside.
            return PassportDocumentRefusal::named(
                $type,
                $slot,
                PassportDocumentRefusal::INTEGRITY_FAILED,
                'the nested DataCredentials failed the transport integrity check or the decrypt.',
            );
        }

        $decoded = json_decode($plaintext, true);
        if (! is_array($decoded)) {
            // §4 says the plaintext is one of three JSON objects. A scalar, a
            // list, or invalid JSON is not a document, and returning one as a
            // document-shaped value would be the silent absence one level down.
            return PassportDocumentRefusal::named(
                $type,
                $slot,
                PassportDocumentRefusal::INTEGRITY_FAILED,
                'the decrypted payload is not a JSON object.',
            );
        }

        return PassportDocument::fromDecrypted($type, $slot, $payloadClass, $decoded);
    }

    /**
     * §6 — `email` / `phone_number`. Never encrypted, never decrypted: the
     * value is the §6 plaintext Telegram already vetted. A refusal here is
     * {@see PassportDocumentRefusal::CLAIM_ABSENT} and nothing else, because
     * §6 makes the claim optional and there is no other honest outcome to
     * report for it.
     */
    public function resolveClaim(string $type, PassportVerifiedClaims $claims): PassportDocument|PassportDocumentRefusal
    {
        $slot = PassportDocumentRefusal::SLOT_PLAIN_DATA;

        $isEmail = $type === 'email';
        // `phone` is the SHIPPED DEFAULT spelling and `phone_number` is the §7
        // one; both name the same claim, and refusing one of them would be the
        // silent absence again — this is why the default allow-list resolves.
        $isPhone = $type === 'phone_number' || $type === 'phone';

        if (! $isEmail && ! $isPhone) {
            return PassportDocumentRefusal::named(
                $type,
                $slot,
                PassportDocumentRefusal::SLOT_ABSENT,
                'plain_data is granted only to the email and phone SecureValueTypes.',
            );
        }

        $value = $isEmail ? $claims->email() : $claims->phone();

        if ($value === null || $value === '') {
            return PassportDocumentRefusal::named(
                $type,
                $slot,
                PassportDocumentRefusal::CLAIM_ABSENT,
                'Telegram verified no such field in this response.',
            );
        }

        // Telegram's OWN field names, not this package's: `email` for the
        // address, `phone_number` for the number, so a host rendering the map
        // needs no translation table.
        return PassportDocument::fromClaim($type, $isEmail ? 'email' : 'phone_number', $value);
    }

    /**
     * One §4 file slot. A vector slot is one call per entry, each addressed by
     * `PassportFileEnvelope::key()`, so `translation[1]` refusing is
     * distinguishable from `translation[0]` succeeding.
     */
    public function resolveFile(PassportFileEnvelope $envelope): PassportDocument|PassportDocumentRefusal
    {
        $type = $envelope->type();
        $slot = $envelope->slot();

        if (! $envelope->hasCredentials()) {
            return PassportDocumentRefusal::named(
                $type,
                $slot,
                PassportDocumentRefusal::FILE_HASH_UNAVAILABLE,
                'this shape carries no file_hash/secret for the file, so it cannot be decrypted by anyone '
                .'(the Bot API PassportFile has file_id/file_size/file_date only; read it over MTProto instead).',
            );
        }

        if (! $envelope->isDownloadable()) {
            return PassportDocumentRefusal::named(
                $type,
                $slot,
                PassportDocumentRefusal::FILE_HASH_UNAVAILABLE,
                'the file reference carries no id/access_hash, so there is nothing to download from.',
            );
        }

        if ($envelope->isOversized()) {
            return PassportDocumentRefusal::named(
                $type,
                $slot,
                PassportDocumentRefusal::FILE_TOO_LARGE,
                'the declared size exceeds the 10 MB passport file ceiling, so it is refused before download.',
            );
        }

        $pem = $this->privateKeyPem;
        if ($pem === null || $pem === '') {
            return PassportDocumentRefusal::named(
                $type,
                $slot,
                PassportDocumentRefusal::WIRE_UNAVAILABLE,
                'no service private key is bound, so no file can be decrypted.',
            );
        }

        $downloaded = $this->download($envelope);
        if ($downloaded['bytes'] === null) {
            return PassportDocumentRefusal::named(
                $type,
                $slot,
                $downloaded['code'],
                $downloaded['detail'],
            );
        }

        try {
            // §4: the transport check runs over the DOWNLOADED bytes, and the
            // decrypt repeats the same `hash_equals` internally.
            PassportCredentialsVerifier::assertFileCredentials(
                $downloaded['bytes'],
                $envelope->rawHashForVerify(),
                $envelope->rawSecretForVerify(),
                $pem,
            );

            $bytes = $envelope->decryptWith($downloaded['bytes'], $pem);
        } catch (\RuntimeException) {
            return PassportDocumentRefusal::named(
                $type,
                $slot,
                PassportDocumentRefusal::INTEGRITY_FAILED,
                'the nested FileCredentials failed the transport integrity check or the decrypt.',
            );
        }

        return PassportDocument::fromFile($type, $slot, $bytes, $envelope->declaredSize());
    }

    /**
     * `upload.getFile` in chunks — the whole file, because a partial decrypt is
     * not a thing: §4 hashes the AES INPUT of the COMPLETE ciphertext, so the
     * bytes must be complete before any of this runs.
     *
     * Returns the bytes on success, or null plus the NAME of the refusal — and
     * the distinction is the point: {@see PassportDocumentRefusal::WIRE_UNAVAILABLE}
     * means "bind the seam", {@see PassportDocumentRefusal::DOWNLOAD_FAILED}
     * means "Telegram refused or gave nothing back", and
     * {@see PassportDocumentRefusal::FILE_TOO_LARGE} means "§4 says 10 MB". A
     * single null for all three would be exactly the undifferentiated
     * absence this whole change exists to remove, and it would leave
     * {@see PassportDocumentRefusal::DOWNLOAD_FAILED} a DECLARED BUT UNREACHABLE
     * code — a vocabulary entry that reads like coverage and is not.
     *
     * @return array{bytes: ?string, code: string, detail: string}
     */
    private function download(PassportFileEnvelope $envelope): array
    {
        $wire = $this->wire;
        if ($wire === null) {
            return [
                'bytes' => null,
                'code' => PassportDocumentRefusal::WIRE_UNAVAILABLE,
                'detail' => 'no passport wire seam is bound, so the file cannot be downloaded (bind '
                    .PassportSupport::SCOPE_KEY.').',
            ];
        }

        $location = $envelope->fileLocation();
        $offset = 0;
        $bytes = '';

        do {
            $chunk = $this->chunk($wire, $location, $offset);
            if ($chunk === null) {
                return [
                    'bytes' => null,
                    'code' => PassportDocumentRefusal::DOWNLOAD_FAILED,
                    'detail' => 'upload.getFile refused, returned a CDN redirect, or returned no bytes.',
                ];
            }

            $bytes .= $chunk;
            $offset += strlen($chunk);

            // §4's ceiling, enforced WHILE assembling so an unbounded response
            // cannot grow the buffer past what we would refuse anyway.
            if (strlen($bytes) > PassportFileEnvelope::MAX_FILE_BYTES) {
                return [
                    'bytes' => null,
                    'code' => PassportDocumentRefusal::FILE_TOO_LARGE,
                    'detail' => 'the downloaded file exceeds the 10 MB passport file ceiling.',
                ];
            }
        } while (strlen($chunk) === PassportFileEnvelope::CHUNK_BYTES);

        if ($bytes === '') {
            return [
                'bytes' => null,
                'code' => PassportDocumentRefusal::DOWNLOAD_FAILED,
                'detail' => 'upload.getFile returned an empty file.',
            ];
        }

        return ['bytes' => $bytes, 'code' => '', 'detail' => ''];
    }

    /**
     * One `upload.getFile` call. `upload.fileCdnRedirect#f18cda44` is a refusal
     * here, not a retry: this seam has no CDN key and no second DC, and
     * pretending otherwise would be the silent absence again. An empty chunk
     * ENDS the file (Telegram's own "you have reached the end" signal), and is
     * reported separately from a refusal by returning `''`.
     *
     * @param  array<string, mixed>  $location
     */
    private function chunk(PassportWireContract $wire, array $location, int $offset): ?string
    {
        try {
            $response = $wire->call(PassportWireContract::METHOD_GET_FILE, [
                'location' => $location,
                'offset' => $offset,
                'limit' => PassportFileEnvelope::CHUNK_BYTES,
            ]);
        } catch (\Throwable) {
            // The typed refusal (FILE_ID_INVALID, FILE_REFERENCE_EXPIRED, a
            // CDN redirect's LOCATION_INVALID, …) is deliberately not read:
            // a named refusal that says WHY Telegram said what it said would
            // let a prober distinguish legs, which is exactly what the single
            // DECRYPT_ERROR message exists to prevent.
            return null;
        }

        $constructor = $response['_'] ?? '';
        if ($constructor !== '' && $constructor !== 'upload.file') {
            return null; // upload.fileCdnRedirect — no CDN seam here
        }

        $bytes = $response['bytes'] ?? null;
        if (! is_string($bytes)) {
            return null;
        }

        return $bytes;
    }

    /** Why an encoding was refused — one sentence, names only, no values. */
    private function faultDetail(string $code): string
    {
        return match ($code) {
            PassportDocumentRefusal::ENCODING_INVALID => 'a §4 field arrived in an encoding this module will not guess at '
                .'(a digest is not 32 raw bytes once decoded).',
            PassportDocumentRefusal::CREDENTIALS_ABSENT => 'Credentials.secure_data carried no data_hash/secret for this type.',
            default => 'the nested credentials could not be read.',
        };
    }
}
