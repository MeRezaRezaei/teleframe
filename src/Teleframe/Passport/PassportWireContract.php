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
