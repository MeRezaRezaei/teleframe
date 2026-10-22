<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * PassportWireContract (Cycle 18 W3) — THE single seam every passport wire
 * call goes through, and the reason the whole wire path is offline-testable
 * without a socket, a session string, or a scope fake that reaches the
 * transport.
 *
 * The shape mirrors CursorStoreContract (src/Teleframe/Backfill/): one
 * interface, two implementations — UserScopePassportWire (the live scope) and
 * MemoryPassportWire (offline) — so src/Teleframe/ stays plain-PHP
 * constructible (bin/standalone-smoke.php) and no class here can reach a
 * container.
 *
 * The five METHOD_* constants are the layer-227 passport/authorization family
 * with their OFFICIAL constructor ids (OFFICIAL_IDS below), verbatim from
 * /home/me/Documents/projects/MadelineProto/src/TL_telegram_v227.tl:2242-2243,
 * :2298-2299, :2719 (mirrored in src/Schema/schema/sources/api_full.tl). The
 * ids are carried HERE as data so a test can pin them against
 * `UserScopeSchema::LINES` — the 2026-09-24 audit read the absence of these
 * methods from the wire schema and wrongly concluded the family was not
 * callable; they simply were not in the hand-curated seed. See
 * docs/superpowers/specs/2026-09-24-passport-login-gap-analysis.md §3.1.
 *
 * Zero-regex: string functions only.
 */
interface PassportWireContract
{
    /** auth.exportAuthorization#e5bfffcd dc_id:int = auth.ExportedAuthorization */
    public const METHOD_EXPORT_AUTHORIZATION = 'auth.exportAuthorization';

    /** auth.importAuthorization#a57a7dad id:long bytes:bytes = auth.Authorization */
    public const METHOD_IMPORT_AUTHORIZATION = 'auth.importAuthorization';

    /** help.getPassportConfig#c661ad08 hash:int = help.PassportConfig */
    public const METHOD_PASSPORT_CONFIG = 'help.getPassportConfig';

    /** account.getAuthorizationForm#a929597a bot_id:long scope:string public_key:string = account.AuthorizationForm */
    public const METHOD_AUTHORIZATION_FORM = 'account.getAuthorizationForm';

    /** account.acceptAuthorization#f3ed4c73 bot_id:long scope:string public_key:string value_hashes:Vector<SecureValueHash> credentials:SecureCredentialsEncrypted = Bool */
    public const METHOD_ACCEPT_AUTHORIZATION = 'account.acceptAuthorization';

    /**
     * upload.getFile#be5335be flags:# precise:flags.0?true cdn_supported:flags.1?true
     *   location:InputFileLocation offset:long limit:int = upload.File
     *
     * THE PASSPORT DOCUMENT DOWNLOAD — and the reason it is NOT `help.getFile`.
     *
     * `help.getFile` is not a Telegram method. It 404s on
     * `core.telegram.org/method/help.getFile`, it is absent from the layer-229
     * catalogue (`src/Schema/schema/methods-mtproto.json`: 823 methods, and the
     * eighteen `help.get*` entries it carries include no file getter), and it is
     * absent from the layer-227 mirror. Upstream's own Passport page routes the
     * download through the ordinary file path, and the location constructor it
     * uses is `inputSecureFileLocation#cbc7ee28 id:long access_hash:long`,
     * which IS in both. See `PassportFileEnvelope` for the full chain.
     *
     * @see PassportFileEnvelope for the download chain this is the middle of.
     */
    public const METHOD_GET_FILE = 'upload.getFile';

    /**
     * users.setSecureValueErrors#90c894b5 id:InputUser
     *   errors:Vector<SecureValueError> = Bool
     *
     * The MTProto twin of the Bot API's `setPassportDataErrors`, and the one
     * this seam dispatches: it is in both the mirror and the catalogue, so it is
     * callable here in a way the Bot API method is not (that one is HTTP, and
     * this module's only wire seam is MTProto). Both live in the nine-member
     * `secureValueError*` family `PassportFieldError` already models.
     */
    public const METHOD_SET_SECURE_VALUE_ERRORS = 'users.setSecureValueErrors';

    /**
     * The official layer-227 constructor id per method, verbatim.
     *
     * @var array<string, string>
     */
    public const OFFICIAL_IDS = [
        self::METHOD_EXPORT_AUTHORIZATION => 'e5bfffcd',
        self::METHOD_IMPORT_AUTHORIZATION => 'a57a7dad',
        self::METHOD_PASSPORT_CONFIG => 'c661ad08',
        self::METHOD_AUTHORIZATION_FORM => 'a929597a',
        self::METHOD_ACCEPT_AUTHORIZATION => 'f3ed4c73',
        self::METHOD_GET_FILE => 'be5335be',
        self::METHOD_SET_SECURE_VALUE_ERRORS => '90c894b5',
    ];

    /**
     * One MTProto call. Implementations MUST let a refusal surface as itself
     * (an `RpcErrorException` keeps its `PASSWORD_REQUIRED` identity so
     * PassportRefusal can route it) — a wire seam that flattens every error
     * into one type would make the 2FA route undetectable.
     *
     * @param  array<string, mixed>  $params  the request body
     * @return array<string, mixed> the decoded response
     *
     * @throws PassportLoginException when no live scope is available (never a silent no-op)
     */
    public function call(string $method, array $params = []): array;
}
