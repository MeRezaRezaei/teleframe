<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use MeRezaRezaei\Teleframe\Core\Passport\PassportDecryptor;

/**
 * PassportFileEnvelope — §4 `FileCredentials { file_hash, secret }` over one
 * downloaded passport FILE, plus the download coordinates it takes to get
 * those bytes.
 *
 * ══ THE DOWNLOAD, AND WHY IT IS `upload.getFile` ══════════════════════════
 *
 * The obvious name here is `help.getFile`, and it is worth recording why that
 * name does not appear anywhere in this package, because the reason is not a
 * style preference:
 *
 *   `help.getFile` is not a Telegram method.
 *
 * It returns HTTP 404 on `core.telegram.org/method/help.getFile`, it is absent
 * from the layer-229 schema catalogue (`src/Schema/schema/methods-mtproto.json`
 * — 823 methods, no `help.*` file getter among the eighteen `help.get*` entries
 * it does carry), and it is absent from the layer-227 wire mirror
 * (`src/Schema/schema/sources/api_full.tl`).
 * The passport section of upstream's own encryption page says the encrypted
 * file "is uploaded chunk by chunk as described in files », except that instead
 * of generating an inputFile, an inputSecureFile should be generated" — and
 * the file-download path it points at is `upload.getFile`.
 *
 * So the chain this class drives is the one upstream actually specifies:
 *
 *   `secureFile#7d09c27e`  id + access_hash          (Telegram sent it)
 *        → `inputSecureFileLocation#cbc7ee28` id + access_hash = InputFileLocation
 *        → `upload.getFile#be5335be` location offset limit = upload.File
 *        → `upload.file#96a18d5` bytes
 *
 * `inputSecureFileLocation` and `upload.getFile` are both in the layer-227
 * mirror AND in the layer-229 catalogue, which is why this is buildable here
 * and a `help.getFile` builder never could have been.
 *
 * ══ THE BOT API HAS NO HALF OF THIS, AND SAYS SO BY REFUSAL ═══════════════
 *
 * A Bot API `PassportFile` carries `file_id`, `file_unique_id`, `file_size`
 * and `file_date` — and no `file_hash`, no `secret`. So on that shape the
 * bytes are downloadable (`getFile(file_id)`) but NOT decryptable by anyone,
 * including Telegram: §4's `FileCredentials` needs the `file_hash` and the
 * sealed `secret`, and the Bot API does not put them on the wire. That is a
 * property of the shape, not a defect in this package, so it is reported as
 * {@see PassportDocumentRefusal::FILE_HASH_UNAVAILABLE} — a name a host can
 * branch on ("ask for this over MTProto") rather than an absence a host has to
 * discover. `hasCredentials()` is the predicate that decides between the two,
 * and it is the ONLY thing that decides: no heuristic, no "try it and see".
 *
 * SECURITY: this object carries an `access_hash` and two raw digests. It has
 * no logger and no `__toString`; the resolver uses it in-process and never
 * renders it. `PassportDocument::toArray()` — the shape a host would log or
 * persist — contains names only.
 *
 * Plain data, zero-regex (`src/Teleframe`), no container, no config.
 */
final class PassportFileEnvelope
{
    /** §4 — "Files are JPG, max 10 MB." A larger one is refused, not buffered. */
    public const MAX_FILE_BYTES = 10485760;

    /** Telegram's 1 MB `upload.getFile` chunk ceiling (a limit, not a quota). */
    public const CHUNK_BYTES = 1048576;

    /** §7 — the single-file slots. */
    public const SINGLE_SLOTS = [
        PassportDocumentRefusal::SLOT_FRONT_SIDE,
        PassportDocumentRefusal::SLOT_REVERSE_SIDE,
        PassportDocumentRefusal::SLOT_SELFIE,
    ];

    /** §7 — the vector-of-files slots. */
    public const VECTOR_SLOTS = [
        PassportDocumentRefusal::SLOT_TRANSLATION,
        PassportDocumentRefusal::SLOT_FILES,
    ];

    private function __construct(
        private readonly string $type,
        private readonly string $slot,
        private readonly int $index,
        private readonly int $id,
        private readonly int $accessHash,
        private readonly string $rawHash,
        private readonly string $rawSecret,
        private readonly int $declaredSize,
        private readonly bool $downloadable,
    ) {}

    /**
     * The MTProto shape — `secureFile#7d09c27e`, which carries the whole
     * §4 `FileCredentials` AND the download coordinates. This is the complete
     * envelope, and it is the only shape in which a passport file can actually
     * be decrypted.
     *
     * `downloadable` is false only for `secureFileEmpty#64199744`, whose
     * constructor carries no id at all.
     *
     * `access_hash` is a `long` in the schema and arrives here already decoded
     * to a PHP int by the TL codec; `fromWire` accepts it as an int and never
     * as a string, because a base64 `access_hash` is a different thing and
     * coercing between them would be a silent reinterpretation.
     */
    public static function fromWire(
        string $type,
        string $slot,
        int $index,
        int $id,
        int $accessHash,
        string $rawHash,
        string $rawSecret,
        int $declaredSize,
    ): self {
        return new self(
            $type,
            $slot,
            $index,
            $id,
            $accessHash,
            $rawHash,
            $rawSecret,
            $declaredSize,
            $id !== 0 && $accessHash !== 0,
        );
    }

    /**
     * The Bot API shape — a `PassportFile`. The download coordinates are
     * deliberately NOT modelled here (a Bot API file is fetched over HTTP by
     * `file_id`, which this package does not own a transport for), because a
     * half-built coordinate set that a caller might use would be worse than an
     * honest refusal: `hasCredentials()` is false, so the resolver reports
     * {@see PassportDocumentRefusal::FILE_HASH_UNAVAILABLE} and stops.
     */
    public static function withoutCredentials(
        string $type,
        string $slot,
        int $index,
        int $declaredSize,
    ): self {
        return new self($type, $slot, $index, 0, 0, '', '', $declaredSize, false);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function slot(): string
    {
        return $this->slot;
    }

    /** Position within a vector slot (`translation[1]`, `files[0]`, …). */
    public function index(): int
    {
        return $this->index;
    }

    /** `type.slot[index]` — the addressable key in the coverage table. */
    public function key(): string
    {
        return $this->slot === '' || $this->type === ''
            ? $this->slot
            : $this->type.'.'.$this->slot.'['.$this->index.']';
    }

    /** Whether §4 `FileCredentials` can be reconstructed at all. */
    public function hasCredentials(): bool
    {
        return $this->rawHash !== '' && $this->rawSecret !== '';
    }

    /** Whether this file can be downloaded (an id + access_hash are present). */
    public function isDownloadable(): bool
    {
        return $this->downloadable;
    }

    /**
     * The raw 32-byte `file_hash` (§4). Never hexits, never base64.
     *
     * Read by `PassportCredentialsVerifier::assertFileCredentials()` — the
     * transport check that runs over the DOWNLOADED bytes, immediately before
     * the decrypt. In-process only: this package never renders it.
     */
    public function rawHashForVerify(): string
    {
        return $this->rawHash;
    }

    /** The raw RSA-OAEP-sealed `secret`. In-process only, never rendered. */
    public function rawSecretForVerify(): string
    {
        return $this->rawSecret;
    }

    public function declaredSize(): int
    {
        return $this->declaredSize;
    }

    public function isOversized(): bool
    {
        return $this->declaredSize > self::MAX_FILE_BYTES;
    }

    /**
     * `inputSecureFileLocation#cbc7ee28` — the §7 download coordinates, in the
     * exact TL shape `upload.getFile` expects. Kept as ONE method so the two
     * numbers and the constructor name cannot drift apart.
     *
     * @return array{_: string, id: int, access_hash: int}
     */
    public function fileLocation(): array
    {
        return [
            '_' => 'inputSecureFileLocation',
            'id' => $this->id,
            'access_hash' => $this->accessHash,
        ];
    }

    /** The exact argument order of `PassportDecryptor::decryptFileCredentials()`. */
    public function decryptWith(string $downloadedBytes, string $privateKeyPem): string
    {
        return PassportDecryptor::decryptFileCredentials(
            $downloadedBytes,
            $this->rawHash,
            $this->rawSecret,
            $privateKeyPem,
        );
    }
}
