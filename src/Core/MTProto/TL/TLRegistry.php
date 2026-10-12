<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\MTProto\TL;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\Schema\UserScopeSchema;
use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;

/**
 * Constructor-name ↔ CRC32-id registry built from canonical TL schema lines.
 * IDs are computed at runtime from the canonical string (single spaces, exact field order).
 *
 * Canonicalization notes (verified against core.telegram.org/mtproto/auth_key,
 * /mtproto/samples-auth_key and /mtproto/service_messages):
 *  - MTProto handshake lines use `string` (not `bytes`) for pq/p/q/dh_prime/g_a/g_b/encrypted_*.
 *  - resPQ's fingerprint list is `Vector long` (no angle brackets) in the canonical string.
 *  - Generic type declarations `{X:Type}` appear brace-less (`X:Type`) in the CRC string.
 *  - rpc_error yields RpcError; the dh_gen_* constructors answer Set_client_DH_params_answer.
 *
 * LAYERS ARE TWO DIFFERENT NUMBERS, ON PURPOSE. The wire advertises layer 227
 * (EncryptedConnection::LAYER). The vendored mirror (schema/sources/*.tl) and
 * the packaged schema catalogue (methods-mtproto.json, stamped in
 * schema-manifest.json) are layer 229. The gap is deliberate; do not reconcile
 * it by editing either number. What must stay true is that nothing calls the
 * 229 content "227" — see mirrorIdDivergences() for the check that keeps that
 * honest.
 */
class TLRegistry
{
    public const VECTOR = 0x1CB5C415;

    /** @var array<string, int> name => id */
    protected static array $ids = [];

    /** @var array<int, string> id => name */
    protected static array $names = [];

    /** @var array<string, string> name => canonical line */
    protected static array $signatures = [];

    /** @var array<string, ParsedSignature> name => parse-once cached struct */
    protected static array $parsed = [];

    protected static bool $booted = false;

    /** @var int|null layer stamped in schema-manifest.json; 0 = unknown, never a guess */
    protected static ?int $catalogueLayer = null;

    public const SCHEMA = [
        'req_pq_multi nonce:int128 = ResPQ',
        'resPQ nonce:int128 server_nonce:int128 pq:string server_public_key_fingerprints:Vector long = ResPQ',
        'p_q_inner_data pq:string p:string q:string nonce:int128 server_nonce:int128 new_nonce:int256 = P_Q_inner_data',
        'p_q_inner_data_dc pq:string p:string q:string nonce:int128 server_nonce:int128 new_nonce:int256 dc:int = P_Q_inner_data',
        'p_q_inner_data_temp_dc pq:string p:string q:string nonce:int128 server_nonce:int128 new_nonce:int256 dc:int expires_in:int = P_Q_inner_data',
        'req_DH_params nonce:int128 server_nonce:int128 p:string q:string public_key_fingerprint:long encrypted_data:string = Server_DH_Params',
        'server_DH_params_ok nonce:int128 server_nonce:int128 encrypted_answer:string = Server_DH_Params',
        'server_DH_params_fail nonce:int128 server_nonce:int128 new_nonce_hash:int128 = Server_DH_Params',
        'server_DH_inner_data nonce:int128 server_nonce:int128 g:int dh_prime:string g_a:string server_time:int = Server_DH_inner_data',
        'client_DH_inner_data nonce:int128 server_nonce:int128 retry_id:long g_b:string = Client_DH_Inner_Data',
        'set_client_DH_params nonce:int128 server_nonce:int128 encrypted_data:string = Set_client_DH_params_answer',
        'dh_gen_ok nonce:int128 server_nonce:int128 new_nonce_hash1:int128 = Set_client_DH_params_answer',
        'dh_gen_retry nonce:int128 server_nonce:int128 new_nonce_hash2:int128 = Set_client_DH_params_answer',
        'dh_gen_fail nonce:int128 server_nonce:int128 new_nonce_hash3:int128 = Set_client_DH_params_answer',
        'invokeWithLayer X:Type layer:int query:!X = X',
        'initConnection X:Type flags:# api_id:int device_model:string system_version:string app_version:string system_lang_code:string lang_pack:string lang_code:string proxy:flags.0?InputClientProxy params:flags.1?JSONValue query:!X = X',
        'help.getNearestDc = NearestDc',
        'nearestDc country:string this_dc:int nearest_dc:int = NearestDc',
        'help.getConfig = Config',
        'auth.importBotAuthorization flags:int api_id:int api_hash:string bot_auth_token:string = auth.Authorization',
        'rpc_result req_msg_id:long result:Object = RpcResult',
        'rpc_error error_code:int error_message:string = RpcError',
        'bad_server_salt bad_msg_id:long bad_msg_seqno:int error_code:int new_server_salt:long = BadMsgNotification',
        'bad_msg_notification bad_msg_id:long bad_msg_seqno:int error_code:int = BadMsgNotification',
        'gzip_packed packed_data:string = Object',
        // --- InputPeer and InputUser constructors ---
        'inputPeerEmpty#7f3b18ea = InputPeer',
        'inputPeerSelf#7da07ec9 = InputPeer',
        'inputPeerChat#35a95cb9 chat_id:long = InputPeer',
        'inputPeerUser#dde8a54c user_id:long access_hash:long = InputPeer',
        'inputPeerChannel#27bcbbfc channel_id:long access_hash:long = InputPeer',
        'inputUserEmpty#b98886cf = InputUser',
        'inputUserSelf#f7c1b13f = InputUser',
        'inputUser#f21158c6 user_id:long access_hash:long = InputUser',
        'inputUserFromMessage#1da448e2 peer:InputPeer msg_id:int user_id:long = InputUser',
        'userProfilePhoto#82d1f706 flags:# has_video:flags.0?true personal:flags.2?true photo_id:long stripped_thumb:flags.1?bytes dc_id:int = UserProfilePhoto',
        'userStatusEmpty#9d05049 = UserStatus',
        'userStatusOnline#edb93949 expires:int = UserStatus',
        'userStatusOffline#8c703f was_online:int = UserStatus',
        'userStatusRecently#7b197dc8 flags:# by_me:flags.0?true = UserStatus',
        'userStatusLastWeek#541a1d1a flags:# by_me:flags.0?true = UserStatus',
        'userStatusLastMonth#65899777 flags:# by_me:flags.0?true = UserStatus',
        'auth.sentCode#5e002502 flags:# type:auth.SentCodeType phone_code_hash:string next_type:flags.1?auth.CodeType timeout:flags.2?int = auth.SentCode',
        'auth.sentCodeTypeApp#3dbb5986 length:int = auth.SentCodeType',
        'auth.sentCodeTypeSms#c000bba2 length:int = auth.SentCodeType',
        'auth.sentCodeTypeCall#5353e5a7 length:int = auth.SentCodeType',
        'auth.sentCodeTypeFlashCall#ab03c6d9 pattern:string = auth.SentCodeType',
        'auth.sentCodeTypeMissedCall#82006484 prefix:string length:int = auth.SentCodeType',
        'auth.sentCodeTypeFragmentSms#d9565c39 url:string length:int = auth.SentCodeType',
        'auth.codeTypeSms#72a3158c = auth.CodeType',
        'auth.codeTypeCall#741cd3e3 = auth.CodeType',
        'auth.codeTypeFlashCall#226ccefb = auth.CodeType',
        'auth.codeTypeMissedCall#d61ad6ee = auth.CodeType',
        'auth.codeTypeFragmentSms#6ed998c = auth.CodeType',
        'auth.authorization#2ea2c0d4 flags:# setup_password_required:flags.1?true otherwise_relogin_days:flags.1?int tmp_sessions:flags.0?int future_auth_token:flags.2?bytes user:User = auth.Authorization',
        'auth.authorizationSignUpRequired#44747e9a flags:# terms_of_service:flags.0?help.TermsOfService = auth.Authorization',
        'account.password#957b50fb flags:# has_recovery:flags.0?true has_secure_values:flags.1?true has_password:flags.2?true current_algo:flags.2?PasswordKdfAlgo srp_B:flags.2?bytes srp_id:flags.2?long hint:flags.3?string email_unconfirmed_pattern:flags.4?string new_algo:PasswordKdfAlgo new_secure_algo:SecurePasswordKdfAlgo secure_random:bytes pending_reset_date:flags.5?int login_email_pattern:flags.6?string = account.Password',
        'passwordKdfAlgoUnknown#d45ab096 = PasswordKdfAlgo',
        'passwordKdfAlgoSHA256SHA256PBKDF2HMACSHA512iter100000SHA256ModPow#3a912d4a salt1:bytes salt2:bytes g:int p:bytes = PasswordKdfAlgo',
        'securePasswordKdfAlgoUnknown#4a8537 = SecurePasswordKdfAlgo',
        'securePasswordKdfAlgoPBKDF2HMACSHA512iter100000#bbf2dda0 salt:bytes = SecurePasswordKdfAlgo',
        'securePasswordKdfAlgoSHA512#86471d92 salt:bytes = SecurePasswordKdfAlgo',
        'inputCheckPasswordSRP#d27ff082 srp_id:long A:bytes M1:bytes = InputCheckPasswordSRP',
        'inputCheckPasswordEmpty#acd9bf6d = InputCheckPasswordSRP',
        'codeSettings#ad253d78 flags:# allow_flashcall:flags.0?true current_number:flags.1?true allow_app_hash:flags.4?true allow_missed_call:flags.5?true allow_firebase:flags.7?true unknown_number:flags.9?true logout_tokens:flags.6?Vector<bytes> token:flags.8?string app_sandbox:flags.8?Bool = CodeSettings',
        'boolTrue#997275b4 = Bool',
        'boolFalse#bc799737 = Bool',
        'auth.sendCode#a677244f phone_number:string api_id:int api_hash:string settings:CodeSettings = auth.SentCode',
        'auth.signIn#8d52a951 flags:# phone_number:string phone_code_hash:string phone_code:flags.0?string email_verification:flags.1?EmailVerification = auth.Authorization',
        'auth.checkPassword#d18b4d16 password:InputCheckPasswordSRP = auth.Authorization',
        'account.getPassword#548a30f5 = account.Password',
        'auth.resendCode#cae47523 flags:# phone_number:string phone_code_hash:string reason:flags.0?string = auth.SentCode',
        'auth.cancelCode#1f04049f phone_number:string phone_code_hash:string = Bool',
        'auth.loginToken#629f1980 expires:int token:bytes = auth.LoginToken',
        'auth.loginTokenMigrateTo#68e9916 dc_id:int token:bytes = auth.LoginToken',
        'auth.loginTokenSuccess#390d5c5e authorization:auth.Authorization = auth.LoginToken',
        'auth.exportLoginToken#b7e085fe api_id:int api_hash:string except_ids:Vector<long> = auth.LoginToken',
        'auth.importLoginToken#95ac5ce4 token:bytes = auth.LoginToken',
        'users.getUsers#d91a548 id:Vector<InputUser> = Vector<User>',
        'users.getFullUser#b60f5918 id:InputUser = users.UserFull',
        'user#31774388 flags:# self:flags.10?true contact:flags.11?true mutual_contact:flags.12?true deleted:flags.13?true bot:flags.14?true bot_chat_history:flags.15?true bot_nochats:flags.16?true verified:flags.17?true restricted:flags.18?true min:flags.20?true bot_inline_geo:flags.21?true support:flags.23?true scam:flags.24?true apply_min_photo:flags.25?true fake:flags.26?true bot_attach_menu:flags.27?true premium:flags.28?true attach_menu_enabled:flags.29?true flags2:# bot_can_edit:flags2.1?true close_friend:flags2.2?true stories_hidden:flags2.3?true stories_unavailable:flags2.4?true contact_require_premium:flags2.10?true bot_business:flags2.11?true bot_has_main_app:flags2.13?true bot_forum_view:flags2.16?true bot_forum_can_manage_topics:flags2.17?true bot_can_manage_bots:flags2.18?true bot_guestchat:flags2.19?true bot_guard:flags2.20?true id:long access_hash:flags.0?long first_name:flags.1?string last_name:flags.2?string username:flags.3?string phone:flags.4?string photo:flags.5?UserProfilePhoto status:flags.6?UserStatus bot_info_version:flags.14?int restriction_reason:flags.18?Vector<RestrictionReason> bot_inline_placeholder:flags.19?string lang_code:flags.22?string emoji_status:flags.30?EmojiStatus usernames:flags2.0?Vector<Username> stories_max_id:flags2.5?RecentStory color:flags2.8?PeerColor profile_color:flags2.9?PeerColor bot_active_users:flags2.12?int bot_verification_icon:flags2.14?long send_paid_messages_stars:flags2.15?long = User',
        'msgs_ack msg_ids:Vector long = MsgsAck',
        'upload.saveFilePart#b304a621 file_id:long file_part:int bytes:string = Bool',
        'upload.saveBigFilePart#de7b673d file_id:long file_part:int file_total_parts:int bytes:string = Bool',
        'upload.getFile#be5335be flags:# precise:flags.0?true cdn_supported:flags.1?true location:InputFileLocation offset:long limit:int = upload.File',
        'upload.file#96a18d5 type:storage.FileType mtime:int bytes:bytes = upload.File',
        'upload.fileCdnRedirect#f18cda44 dc_id:int file_token:string encryption_key:string encryption_iv:string cdn_file_hashes:Vector<CdnFileHash> = upload.File',
        'documentAttributeFilename#15590068 file_name:string = DocumentAttribute',
        'updateChannel#635b4c09 channel_id:long = Update',
        'chatPhotoEmpty#37c1011c = ChatPhoto;',
        'chatPhoto#1c6e1c11 flags:# has_video:flags.0?true photo_id:long stripped_thumb:flags.1?bytes dc_id:int = ChatPhoto;',
        'updateNewChannelMessage#62ba04d9 message:Message pts:int pts_count:int = Update',
        'updateReadChannelInbox#922e6e10 flags:# folder_id:flags.0?int channel_id:long max_id:int still_unread_count:int pts:int = Update',
        'updateReadHistoryInbox#9e84bc99 flags:# folder_id:flags.0?int peer:Peer top_msg_id:flags.1?int max_id:int still_unread_count:int pts:int pts_count:int = Update',
        'updateChatParticipantAdd#3dda5451 chat_id:long user_id:long inviter_id:long date:int version:int = Update',
        'messageEmpty#90a6ca84 flags:# id:int peer_id:flags.0?Peer = Message',
        'messageService#7a800e0a flags:# out:flags.1?true mentioned:flags.4?true media_unread:flags.5?true reactions_are_possible:flags.9?true silent:flags.13?true post:flags.14?true legacy:flags.19?true id:int from_id:flags.8?Peer peer_id:Peer saved_peer_id:flags.28?Peer reply_to:flags.3?MessageReplyHeader date:int action:MessageAction reactions:flags.20?MessageReactions ttl_period:flags.25?int = Message',
        'messageActionChatCreate#bd47cbad title:string users:Vector<long> = MessageAction',
        'messageActionChannelCreate#95d2ac92 title:string = MessageAction',
        'messageActionPinMessage#94bd38ed = MessageAction',
        'chatEmpty#29562865 id:long = Chat',
        'channelForbidden#17d493d5 flags:# broadcast:flags.5?true megagroup:flags.8?true monoforum:flags.10?true id:long access_hash:long title:string until_date:flags.16?int = Chat',
        'documentAttributeImageSize#6c37c15c w:int h:int = DocumentAttribute',
        'inputDocumentFileLocation#bad07584 id:long access_hash:long file_reference:bytes thumb_size:string = InputFileLocation',
        'inputPhotoFileLocation#40181ffe id:long access_hash:long file_reference:bytes thumb_size:string = InputPhotoLocation',
        'storage.fileUnknown#aa963b05 = storage.FileType',
        'storage.filePartial#40bc6f52 = storage.FileType',
        'storage.fileJpeg#7efe0e = storage.FileType',
        'storage.filePng#a4f63c0 = storage.FileType',
        'storage.fileGif#cae1aadf = storage.FileType',
        'storage.filePdf#ae1e508d = storage.FileType',
        'storage.fileDoc#5baa5af5 = storage.FileType',
        'storage.filePhoto#e1a4bf1c = storage.FileType',
        'storage.fileAudio#523a3380 = storage.FileType',
        'storage.fileVideo#f8ec1b06 = storage.FileType',
        'storage.fileSticker#e9149ba1 = storage.FileType',
        'storage.fileAnim#f09cc883 = storage.FileType',
        'storage.fileVoice#ae2d9eda = storage.FileType',
        'storage.fileWebp#2d298958 = storage.FileType',
        'inputFileBig#fa4f0bb5 id:long parts:int name:string = InputFile',
        'inputMediaUploadedDocument#37c9330 flags:# nosound_video:flags.3?true force_file:flags.4?true spoiler:flags.5?true file:InputFile thumb:flags.2?InputFile mime_type:string attributes:Vector<DocumentAttribute> stickers:flags.0?Vector<InputDocument> video_cover:flags.6?InputPhoto video_timestamp:flags.7?int ttl_seconds:flags.1?int = InputMedia',

        'new_session_created first_msg_id:long unique_id:long server_salt:long = NewSession',
        // MTProto keepalive constructors (core.telegram.org/mtproto/service_messages)
        'pong#347773c5 msg_id:long ping_id:long = Pong',
        'ping_delay_disconnect#f3427b8c ping_id:long disconnect_delay:int = Pong',
    ];

    protected static function boot(): void
    {
        if (static::$booted) {
            return;
        }
        static::$booted = true; // set before seeding: register() re-enters boot()
        foreach (self::SCHEMA as $line) {
            self::register($line);
        }
        // Curated constructor closure for the documented scope methods. These
        // lines are hand-written against the 227 wire, so they are the only
        // place in this class where "227" is a claim about the right content.
        foreach (UserScopeSchema::LINES as $line) {
            self::register($line);
        }

        // The vendored mirror (types + functions, explicit ids) — loaded AFTER
        // the curated SCHEMA so hand-listed lines win on id conflicts; kills
        // stale-field decode misalignment wholesale (found live: stale
        // documentAttributeVideo fields overrunning into string bytes).
        //
        // LAYER TRUTH, stated plainly because it used to be mislabelled here:
        // this mirror is LAYER-229 content. `api_full.tl` declares
        // `messages.sendMessage#fef48f62` with `suggested_post:flags.22?` and
        // `rich_message:flags.23?`, and that id is the layer-229 id the schema
        // catalogue carries. The wire, meanwhile, advertises layer 227
        // (EncryptedConnection::LAYER). Those two numbers differ and that gap
        // is DELIBERATE — never "reconciled" by editing either one. What was
        // wrong, and was the actual defect, is the LABEL: 229 bytes were
        // described as a "Full v227 mirror", so every downstream claim derived
        // from this file inherited a layer it never had. Constructor ids are
        // stable across these layers, so the mismatch is tolerated rather than
        // fatal — but a field that exists only in 229 (noforwards,
        // suggested_post, rich_message) is exactly how silent data becomes
        // lost or un-decodable, so nothing here may imply otherwise.
        //
        // `mirrorIdDivergences()` is the guard that makes the claim checkable:
        // it compares every mirror id against the schema catalogue's id for
        // the same constructor, so a future re-snapshot that really does move
        // layers, or a mislabelled mirror, shows up as a failing test instead
        // of a comment nobody re-reads.
        foreach (self::mirrorLines() as $line) {
            $mirrorName = substr($line, 0, (int) strpos($line, '#'));
            if (isset(static::$ids[$mirrorName])) {
                continue; // curated SCHEMA / UserScopeSchema win; mirror only fills gaps
            }
            try {
                self::register($line);
            } catch (\Throwable $e) {
                continue; // generic wrapper lines ({X:Type}) are hand-built at call sites
            }
        }
    }

    /**
     * The vendored TL mirror files, in load order.
     *
     * @var list<string>
     */
    public const MIRROR_FILES = ['api_full.tl', 'mtproto_full.tl'];

    /**
     * Every id-bearing line of the vendored mirror, in file order.
     *
     * Shared by boot() and mirrorIdDivergences() so the loader and the guard
     * can never disagree about what "a mirror line" means.
     *
     * @return list<string>
     */
    public static function mirrorLines(): array
    {
        $lines = [];
        foreach (self::MIRROR_FILES as $file) {
            foreach (self::mirrorFileLines($file) as $line) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * Id-bearing lines of one mirror file, in file order.
     *
     * @return list<string>
     */
    private static function mirrorFileLines(string $file): array
    {
        try {
            $path = SchemaArtifacts::source($file);
        } catch (\Throwable) {
            $path = __DIR__.'/../../../schema/sources/'.$file;
        }
        if (! is_file($path)) {
            return [];
        }

        $out = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $raw) {
            $line = trim($raw, " \t;");
            if ($line === '' || $line[0] === '/' || str_starts_with($line, '---')) {
                continue;
            }
            if (! str_contains($line, '#') || str_contains($line, '{')) {
                // id-less cosmetic lines: curated SCHEMA owns them;
                // brace `{X:Type}` generic wrappers are hand-built at call sites.
                continue;
            }
            $out[] = $line;
        }

        return $out;
    }

    /**
     * The layer the packaged schema catalogue was generated for, read from
     * schema/schema-manifest.json (stamped by `teleframe:schema-update`).
     *
     * This is the layer the vendored mirror and methods-mtproto.json actually
     * contain. It is NOT the layer the wire advertises — see
     * EncryptedConnection::LAYER — and the two are expected to differ.
     * Returns 0 when the manifest is missing or unreadable, which callers
     * must treat as "unknown", never as "same as the wire".
     */
    public static function catalogueLayer(): int
    {
        if (static::$catalogueLayer !== null) {
            return static::$catalogueLayer;
        }

        static::$catalogueLayer = 0;
        try {
            $raw = @file_get_contents(SchemaArtifacts::path('schema-manifest.json'));
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded) && isset($decoded['layer']) && is_int($decoded['layer'])) {
                    static::$catalogueLayer = $decoded['layer'];
                }
            }
        } catch (\Throwable) {
            // absent manifest = unknown layer, not a guessed one
        }

        return static::$catalogueLayer;
    }

    /**
     * Divergences that are known, named, and not yet fixed.
     *
     * The guard above is a HARD failure on any divergence NOT listed here, so
     * new drift cannot land quietly. Each entry carries its reason so the
     * exemption is a decision on the record rather than a silent allow-list.
     * An entry that stops diverging is equally a failure (the exemption has
     * gone stale and should be deleted), so this table cannot rot either way.
     *
     * Currently: `api_full.tl` is an older snapshot than `api.tl` /
     * methods-mtproto.json for exactly one constructor. api_full.tl declares
     * `messages.searchGlobal#4bc6589a` (no `community:flags.4?InputChannel`
     * field); the catalogue declares `messages.searchGlobal#6126a43c` WITH
     * that field. Both ids are real, from different layers, so this is a
     * vendored-artefact skew — not reconcilable by editing a layer number, and
     * not fixable by hand here because `api_full.tl` is a generated artefact
     * (`teleframe:schema-update` owns it). The consequence is real and worth
     * stating: the registry serves #4bc6589a for this method, so a call to
     * messages.searchGlobal encodes the older constructor. The fix is a mirror
     * re-snapshot, not a comment.
     *
     * @var array<string, string> constructor name => why the divergence is tolerated
     */
    public const KNOWN_MIRROR_DIVERGENCES = [
        'messages.searchGlobal' => 'api_full.tl predates the catalogue here (#4bc6589a vs #6126a43c, `community` field); generated artefact, fix by re-snapshotting the mirror',
    ];

    /**
     * Do two hex constructor ids (as written in a .tl file or the JSON
     * catalogue) name the same constructor?
     *
     * Public and pure so the guard's own comparison can be falsified by a
     * test: a guard that cannot be shown to return a difference might just be
     * a guard that never looks. Case and `0x`/`0` padding are ignored; anything
     * else is a real difference.
     */
    public static function idsAgree(string $mirrorId, string $catalogueId): bool
    {
        return strtolower(ltrim($mirrorId, '0x')) === strtolower(ltrim($catalogueId, '0x'));
    }

    /**
     * Report every id disagreement between the vendored mirror and the schema
     * catalogue, bucketed by whether it is expected.
     *
     * A constructor id is a CRC32 of the canonical signature, so if the mirror
     * and the catalogue disagree on it, one of the two is from a different
     * Telegram layer than the other — exactly the fact that must never be
     * papered over with a comment. This is the check that would have caught
     * `api_full.tl` being called a "v227 mirror" when it carries layer-229
     * fields (`suggested_post`, `rich_message`): the ids are the evidence.
     *
     * Three buckets, so nothing rots in either direction:
     *  - expected:   named in KNOWN_MIRROR_DIVERGENCES (a recorded decision)
     *  - unexpected: NOT named — this is the hard-fail set
     *  - stale:      named but no longer diverging — delete the exemption
     *
     * It proves the two ARTEFACTS agree with each other. It does not, and
     * cannot, reconcile the wire's 227 with the catalogue's 229.
     *
     * Only functions present in methods-mtproto.json are compared; the mirror
     * also carries types and MTProto service constructors, which the catalogue
     * does not list and which are therefore not evidence of drift.
     *
     * @return array{expected: list<array{name: string, mirror: string, catalogue: string}>, unexpected: list<array{name: string, mirror: string, catalogue: string}>, stale: list<string>}
     */
    public static function mirrorIdDivergenceReport(): array
    {
        $expected = [];
        $unexpected = [];
        foreach (self::mirrorIdDivergences() as $divergence) {
            if (array_key_exists($divergence['name'], self::KNOWN_MIRROR_DIVERGENCES)) {
                $expected[] = $divergence;

                continue;
            }
            $unexpected[] = $divergence;
        }

        $stillDiverging = [];
        foreach ($expected as $divergence) {
            $stillDiverging[$divergence['name']] = true;
        }
        $stale = [];
        foreach (self::KNOWN_MIRROR_DIVERGENCES as $name => $reason) {
            if (! isset($stillDiverging[$name])) {
                $stale[] = (string) $name;
            }
        }

        return [
            'expected' => $expected,
            'unexpected' => $unexpected,
            'stale' => $stale,
        ];
    }

    /**
     * Raw id disagreements, named or not.
     *
     * @return list<array{name: string, mirror: string, catalogue: string}> empty when the two agree
     */
    public static function mirrorIdDivergences(): array
    {
        static $cache;
        if ($cache !== null) {
            return $cache;
        }

        $catalogue = self::catalogueIds();
        $cache = [];
        if ($catalogue === []) {
            return $cache;
        }

        foreach (self::mirrorLines() as $line) {
            $hash = strpos($line, '#');
            if ($hash === false) {
                continue;
            }
            $name = substr($line, 0, $hash);
            $rest = substr($line, $hash + 1);
            $space = strpos($rest, ' ');
            $mirrorId = $space === false ? $rest : substr($rest, 0, $space);
            if ($mirrorId === '' || ! isset($catalogue[$name])) {
                continue;
            }
            if (self::idsAgree($mirrorId, $catalogue[$name])) {
                continue;
            }
            $cache[] = [
                'name' => $name,
                'mirror' => $mirrorId,
                'catalogue' => $catalogue[$name],
            ];
        }

        return $cache;
    }

    /**
     * constructor name => lower-case hex id, from the packaged method catalogue.
     *
     * @return array<string, string>
     */
    private static function catalogueIds(): array
    {
        static $cache;
        if ($cache !== null) {
            return $cache;
        }

        $cache = [];
        try {
            $raw = @file_get_contents(SchemaArtifacts::path('methods-mtproto.json'));
        } catch (\Throwable) {
            $raw = false;
        }
        if (! is_string($raw) || $raw === '') {
            return $cache;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded) || ! isset($decoded['methods']) || ! is_array($decoded['methods'])) {
            return $cache;
        }

        foreach ($decoded['methods'] as $name => $entry) {
            if (is_array($entry) && isset($entry['id']) && is_string($entry['id'])) {
                $cache[(string) $name] = strtolower(ltrim($entry['id'], '0x'));
            }
        }

        return $cache;
    }

    public static function register(string $canonicalLine): void
    {
        self::boot(); // seeding must not depend on call order: register-before-lookup still seeds SCHEMA
        // Wrapper lines ('invokeWithLayer', 'initConnection') carry generic
        // declarations `{X:Type}` / `X:Type` and `!X` bound-variable uses that
        // the strict tokenizer now parses directly — no degraded fallback.
        $name = self::constructorNameOf($canonicalLine);
        self::assertCanonicalizableGeneric($name, $canonicalLine);
        $parsed = self::parseStrictly($canonicalLine);
        $id = $parsed->hasExplicitId ? $parsed->id : self::crc32Canonical(self::canonicalize($canonicalLine));
        static::$parsed[$name] = $parsed;
        static::$ids[$name] = $id;
        static::$names[$id] = $name;
        static::$signatures[$name] = $canonicalLine;
    }

    /**
     * Constructor name of a canonical line: the first space-delimited token
     * with any `#id` suffix stripped. String functions only — no regex.
     */
    private static function constructorNameOf(string $canonicalLine): string
    {
        $line = ltrim($canonicalLine);
        $space = strpos($line, ' ');
        $firstToken = $space === false ? $line : substr($line, 0, $space);
        $hash = strpos($firstToken, '#');

        return $hash === false ? $firstToken : substr($firstToken, 0, $hash);
    }

    /**
     * The canonical string a constructor id is a crc32 OF.
     *
     * A TL constructor id is a crc32 over the canonical definition, and the
     * canonical definition never carries the braces of a generic type-variable
     * declaration. That is not this class's invention: `TlCanon` states it as
     * normalization rule 9 ("'{' and '}' removed (generic type args)"), and
     * `crc32Canonical()` below says outright that "callers must pass already-
     * canonical lines". `{X:Type}` canonicalizes to `X:Type`; nothing else in
     * a TL line contains a brace.
     *
     * Registering the BRACED form used to hash the raw line instead, which is
     * how a generic wrapper would have gone on the wire under an invented id:
     *
     *   invokeAfterMsg {X:Type} msg_id:long query:!X = X  ->  0x59729436
     *   invokeAfterMsg X:Type   msg_id:long query:!X = X  ->  0xcb9f372d
     *
     * The second number is the real one — it is what `sources/api.tl` declares
     * (`invokeAfterMsg#cb9f372d`) and what `MtprotoServerSession::INVOKE_AFTER_MSG`
     * carries as its own hand-held constant. Canonicalizing here is what makes
     * the two agree; the discrepancy is a wrong id, not a missing one, so it
     * would have been silent.
     *
     * Pure and public so the property is falsifiable without touching the
     * registry's static state. String functions only — `src/Core` is
     * strictly zero-regex.
     */
    public static function canonicalize(string $canonicalLine): string
    {
        return str_replace(['{', '}'], '', $canonicalLine);
    }

    /**
     * Refuse, BY NAME, a generic declaration that has no canonical string at
     * all — so its id cannot be derived and any number would be invented.
     *
     * The one real case in the committed sources is the bare container
     * `vector {t:Type} # [ t ] = Vector t` (`sources/api.tl:6`,
     * `sources/mtproto_full.tl:3`). Its `#` and `[ t ]` are schema-level
     * pseudo-fields describing the element shape; they are in no canonical
     * string, and `TlCanon` has no rule that folds them away. `[` / `]` is the
     * marker for that shape, and appears on no other line in any committed
     * source — the 11 request envelopes carry no bracket at all. So this is
     * refused up front, naming the constructor, instead of falling through to
     * `parseStrictly()` and surfacing as "expected field name" against a
     * perfectly valid .tl line. (`#` is deliberately not the marker: every
     * id-bearing line has one, including this one.)
     *
     * The 11 request envelopes are NOT in this set: `{X:Type} ... query:!X = X`
     * canonicalizes exactly, and `register()` is why `invokeWithLayer` and
     * `initConnection` hold the right ids today.
     *
     * Runs BEFORE any parse and before any static mutation, so a refusal
     * leaves the registry exactly as it found it.
     *
     * @throws InvalidArgumentException naming the constructor
     */
    private static function assertCanonicalizableGeneric(string $name, string $canonicalLine): void
    {
        if (! str_contains($canonicalLine, '{')) {
            return;
        }

        foreach (['[', ']'] as $pseudoField) {
            if (! str_contains($canonicalLine, $pseudoField)) {
                continue;
            }

            throw new InvalidArgumentException(
                "TLRegistry: generic '{$name}' cannot be registered — its declaration "
                ."'{$canonicalLine}' carries the schema-level pseudo-field '{$pseudoField}', which is not part of "
                .'any TL canonical string, so its constructor id cannot be derived rather than guessed. The codec '
                .'addresses this container by its fixed id (TLRegistry::VECTOR, 0x1cb5c415) instead of a signature.',
            );
        }
    }

    private static function parseStrictly(string $canonicalLine): ParsedSignature
    {
        try {
            return TLSignatureParser::parse($canonicalLine);
        } catch (InvalidArgumentException $e) {
            // A malformed schema line (e.g. a generated UserScopeSchema line) must
            // fail loudly at boot, naming the offending line.
            throw new InvalidArgumentException(
                "TLRegistry: malformed schema line '{$canonicalLine}': {$e->getMessage()}",
                0,
                $e,
            );
        }
    }

    public static function id(string $constructorName): int
    {
        self::boot();
        if (! isset(static::$ids[$constructorName])) {
            throw new InvalidArgumentException("TLRegistry: unknown constructor '{$constructorName}'");
        }

        return static::$ids[$constructorName];
    }

    public static function signature(string $constructorName): string
    {
        self::boot();
        if (! isset(static::$signatures[$constructorName])) {
            throw new InvalidArgumentException("TLRegistry: unknown constructor '{$constructorName}'");
        }

        return static::$signatures[$constructorName];
    }

    /**
     * Parse-once cached struct for a registered constructor. Repeated calls
     * return the same immutable ParsedSignature instance.
     */
    public static function signatureOf(string $name): ParsedSignature
    {
        self::boot();

        return static::$parsed[$name] ?? throw new InvalidArgumentException("TLRegistry: unknown constructor '{$name}'");
    }

    public static function nameOf(int $id): ?string
    {
        self::boot();

        return static::$names[$id] ?? null;
    }

    /**
     * Can the encoder build this constructor AT ALL?
     *
     * The honest oracle for "is this method on the wire", and the one place
     * that knows the answer: the loaded mirror files PLUS the hand-built
     * generic wrappers in {@see self::SCHEMA} (`invokeWithLayer`,
     * `initConnection`), which the mirror index cannot see because the loader
     * skips brace-bearing lines. `Schema\WireSchemaCoverage` asks this rather
     * than re-deriving it, so the two can never tell different stories.
     */
    public static function isRegistered(string $constructorName): bool
    {
        self::boot();

        return isset(static::$signatures[$constructorName]);
    }

    /**
     * crc32 of the canonical string, interpreted as little-endian 32-bit.
     * Reference canonicalization is "single spaces, nothing trimmed";
     * callers must pass already-canonical lines.
     */
    public static function crc32Canonical(string $line): int
    {
        return (int) sprintf('%u', crc32($line));
    }
}
