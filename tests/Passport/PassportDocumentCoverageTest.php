<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Core\Methods\Generated\Upload;
use MeRezaRezaei\Teleframe\Core\Methods\Generated\Users;
use MeRezaRezaei\Teleframe\Core\Passport\PassportDecryptor;
use MeRezaRezaei\Teleframe\Passport\EncryptedCredentials;
use MeRezaRezaei\Teleframe\Passport\MemoryBindingWriter;
use MeRezaRezaei\Teleframe\Passport\MemoryNonceRegistry;
use MeRezaRezaei\Teleframe\Passport\MemoryPassportWire;
use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use MeRezaRezaei\Teleframe\Passport\PassportDocument;
use MeRezaRezaei\Teleframe\Passport\PassportDocumentCoverage;
use MeRezaRezaei\Teleframe\Passport\PassportDocumentRefusal;
use MeRezaRezaei\Teleframe\Passport\PassportDocumentResolver;
use MeRezaRezaei\Teleframe\Passport\PassportDocumentSource;
use MeRezaRezaei\Teleframe\Passport\PassportErrorReporter;
use MeRezaRezaei\Teleframe\Passport\PassportKeyLoader;
use MeRezaRezaei\Teleframe\Passport\PassportLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportPayload;
use MeRezaRezaei\Teleframe\Passport\PassportWireContract;
use MeRezaRezaei\Teleframe\Passport\SessionIdentityResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * PassportDocumentCoverageTest — THE GATE.
 *
 * ══ WHAT IT PINS, AND WHY A GATE WAS NEEDED AT ALL ════════════════════════
 *
 * Before the document half existed, `allowed_types` accepted eleven §5 document
 * types and `PassportValueValidator` checked them BY NAME. Nothing ever opened
 * one. A passport response therefore produced a `LoginResult` whose
 * `fields_verified` said `passport` while no passport had been read, and the
 * whole existing suite was green, because "green" only ever meant "the name was
 * on the list". A host could not tell that outcome apart from a login that
 * worked perfectly.
 *
 * The rule that closes it:
 *
 *   > **every `allowed_types` value produces a document OR A NAMED REFUSAL.
 *   > Never a silent absence.**
 *
 * `test_every_allowed_type_in_the_whole_section7_vocabulary_produces_a_document_or_a_named_refusal`
 * is that rule, stated as a table, over the FULL §7 vocabulary — not the three
 * types the shipped default happens to allow. The rest are what make it a GATE
 * rather than a snapshot:
 *
 *   - the documents are REAL: every `data` row is decrypted with genuine
 *     RSA-OAEP + AES-256-CBC by the SPEC-FAITHFUL `SpecCredentialFactory`
 *     against a keypair generated per run, and the file row comes off a
 *     scripted `upload.getFile` on the MTProto seam. So "it produced a
 *     document" is not "it produced an object of a document class";
 *   - a TAMPERED payload, a MIS-ENCODED digest, a refused download, a missing
 *     seam, an absent §6 claim and an empty slot are each NAMED — and
 *     `test_every_declared_refusal_code_is_reachable_and_nothing_else_is`
 *     pins the closed vocabulary in both directions, so a code cannot be
 *     declared and unreachable (a vocabulary entry that reads like coverage and
 *     is not) nor reachable and undeclared;
 *   - hostile allow-lists (an unknown name, the empty string, a duplicate)
 *     still get at least one row, because "there is nothing to say about this
 *     one" IS the defect;
 *   - and both `setPassportDataErrors` spellings are asserted, so a refusal that
 *     cannot be named to Telegram is itself visible.
 *
 * NOTHING here calls `markTestSkipped`, weakens an assertion, or deletes a
 * test. Nothing reads `.env`, a session string, a bot token or a live account:
 * the keypair is generated in-process, the PEM lives in a temp file this class
 * removes, and no Telegram endpoint is contacted.
 */
final class PassportDocumentCoverageTest extends TestCase
{
    /** §7 / the Bot API `EncryptedPassportElement.type` vocabulary, all 13. */
    private const FULL_VOCABULARY = [
        'personal_details',
        'email',
        'phone_number',
        'passport',
        'internal_passport',
        'driver_license',
        'identity_card',
        'address',
        'utility_bill',
        'bank_statement',
        'rental_agreement',
        'passport_registration',
        'temporary_registration',
    ];

    /**
     * §4's file ceiling plus one byte. Quoted as a literal rather than read
     * from `PassportFileEnvelope::MAX_FILE_BYTES` so the oversized fixture
     * cannot drift with the constant it is testing against.
     */
    private const OVERSIZED_SIZE = 10485761;

    private SpecCredentialFactory $factory;

    private string $privatePem = '';

    private string $pemFile = '';

    protected function setUp(): void
    {
        $this->factory = new SpecCredentialFactory;
        $this->privatePem = $this->factory->privateKeyPem();

        $this->pemFile = (string) tempnam(sys_get_temp_dir(), 'teleframe-passport-doc-');
        file_put_contents($this->pemFile, $this->privatePem);
        chmod($this->pemFile, 0o600);
    }

    protected function tearDown(): void
    {
        if ($this->pemFile !== '' && is_file($this->pemFile)) {
            unlink($this->pemFile);
        }
    }

    // ── THE TABLE — the deliverable ────────────────────────────────────

    /**
     * THE DELIVERABLE. Every §7 vocabulary entry, asked for, produces at least
     * one row; every row is a decrypted document or a NAMED refusal; and the
     * `data`-bearing types really decrypt rather than being refused wholesale.
     */
    public function test_every_allowed_type_in_the_whole_section7_vocabulary_produces_a_document_or_a_named_refusal(): void
    {
        $update = $this->botApiUpdate(self::FULL_VOCABULARY);
        $coverage = $this->coverage($update, self::FULL_VOCABULARY);

        self::assertTrue(
            $coverage->isComplete(),
            'every allowed_types value must have at least one row: '.json_encode($coverage->toArray()),
        );

        foreach (self::FULL_VOCABULARY as $type) {
            self::assertNotSame(
                [],
                $coverage->for($type),
                'allowed_types value "'.$type.'" produced no row at all — a silent absence.',
            );
        }

        // Every §5 DATA-bearing type genuinely decrypted. This is what stops the
        // table from passing by refusing everything, which is what the
        // pre-document build effectively did.
        foreach (['personal_details', 'passport', 'internal_passport', 'driver_license', 'identity_card', 'address'] as $type) {
            $row = self::rowFor($coverage, $type, PassportDocumentRefusal::SLOT_DATA);

            self::assertInstanceOf(
                PassportDocument::class,
                $row,
                $type.'.data must be a decrypted document, not a refusal.',
            );
            self::assertSame(
                PassportDocument::DATA_PAYLOAD_CLASSES[$type],
                $row->payloadClass,
                $type.' must decrypt to its own §4 payload class.',
            );
        }

        // A decrypted payload really carries its fields, read from the §4
        // payload and NOT from a `secure_data` coordinate Telegram never writes.
        self::assertSame('Ada', self::rowFor($coverage, 'personal_details', PassportDocumentRefusal::SLOT_DATA)->value('first_name'));

        // §6 claims are documents too: a verified email is a value Telegram
        // vouched for, not an absence.
        self::assertInstanceOf(
            PassportDocument::class,
            self::rowFor($coverage, 'email', PassportDocumentRefusal::SLOT_PLAIN_DATA),
        );
        self::assertInstanceOf(
            PassportDocument::class,
            self::rowFor($coverage, 'phone_number', PassportDocumentRefusal::SLOT_PLAIN_DATA),
        );

        // The five file-only types get EXACTLY the two §7 slots and no `data`
        // row at all — which is the stronger statement, because "asked for a
        // data field §7 does not grant it" is then not merely refused, it is
        // not expressible.
        foreach (PassportDocument::FILE_ONLY_TYPES as $type) {
            $slots = [];
            foreach ($coverage->for($type) as $row) {
                $slots[] = $row->slot;
            }

            self::assertSame(
                [PassportDocumentRefusal::SLOT_FILES, PassportDocumentRefusal::SLOT_TRANSLATION],
                $slots,
                $type.' must answer exactly the two slots §7 grants it.',
            );
        }

        // A Bot API file cannot be decrypted by anyone: `PassportFile` carries
        // no `file_hash`/`secret`. Named, never absent.
        $frontSide = self::rowFor($coverage, 'passport', PassportDocumentRefusal::SLOT_FRONT_SIDE);

        self::assertInstanceOf(PassportDocumentRefusal::class, $frontSide);
        self::assertTrue(
            $frontSide->is(PassportDocumentRefusal::FILE_HASH_UNAVAILABLE),
            'a Bot API PassportFile has no file_hash, so the refusal must NAME that.',
        );
    }

    /**
     * THE GATE'S HOSTILE HALF. An allow-list a host can actually write —
     * including names Telegram does not have — must still produce a row.
     *
     * @return array<string, array{list<string>}>
     */
    public static function hostileAllowLists(): array
    {
        return [
            'a name §7 does not have' => [['not_a_real_type', PassportDocument::FILE_ONLY_TYPES[0]]],
            'the empty string' => [['', 'passport']],
            'the same type twice' => [['passport', 'passport']],
            'only email' => [['email']],
            'the shipped default three' => [['personal_details', 'email', 'phone']],
            'a single file-only type' => [['utility_bill']],
        ];
    }

    /**
     * @param  list<string>  $allowedTypes
     */
    #[DataProvider('hostileAllowLists')]
    public function test_no_allow_list_can_produce_a_rowless_type(array $allowedTypes): void
    {
        $coverage = $this->coverage($this->botApiUpdate(self::FULL_VOCABULARY), $allowedTypes);

        self::assertTrue($coverage->isComplete(), json_encode($coverage->toArray()));

        foreach ($allowedTypes as $type) {
            self::assertNotSame(
                [],
                $coverage->for($type),
                'allowed_types entry "'.$type.'" produced no row — a silent absence.',
            );
        }
    }

    /**
     * The row-count floor, isolated. A type outside the §7 table grants no
     * slots, so the §7 walk produces nothing — and exactly one named refusal
     * must stand in. If this ever returns [], the table is countable again and
     * the defect is back.
     */
    public function test_a_type_outside_the_section7_table_still_gets_one_named_refusal(): void
    {
        $rows = $this->coverage($this->botApiUpdate(self::FULL_VOCABULARY), ['telephone_photo'])->for('telephone_photo');

        self::assertCount(1, $rows);
        self::assertInstanceOf(PassportDocumentRefusal::class, $rows[0]);
        self::assertTrue($rows[0]->is(PassportDocumentRefusal::SLOT_ABSENT));
        self::assertStringContainsString('Allowed fields table', $rows[0]->message());
    }

    /**
     * THE CLOSED VOCABULARY, PINNED IN BOTH DIRECTIONS.
     *
     * This is what stops the vocabulary from rotting in either direction: a
     * DECLARED-but-unreachable code is a dead entry that reads like coverage
     * (exactly the `setPassportDataErrors` defect class this lane exists to
     * kill), and a REACHABLE-but-undeclared code is a silent absence wearing a
     * `match` arm that never matched.
     *
     * Every fixture below triggers a DIFFERENT code, and the union of what they
     * produce must equal {@see PassportDocumentRefusal::CODES} exactly.
     */
    public function test_every_declared_refusal_code_is_reachable_and_nothing_else_is(): void
    {
        $reached = [];

        // `slot_absent` — a file-only type asked for a `data` field.
        $reached = array_merge($reached, self::codesOf(
            $this->coverage($this->botApiUpdate(self::FULL_VOCABULARY), ['utility_bill']),
        ));

        // `claim_absent` — email allowed, Telegram verified none.
        $reached = array_merge($reached, self::codesOf(
            $this->coverage($this->botApiUpdate(['passport']), ['email']),
        ));

        // `document_absent` — a §7-granted slot on a type Telegram never sent.
        $reached = array_merge($reached, self::codesOf(
            $this->coverage($this->botApiUpdate(['personal_details']), ['passport']),
        ));

        // `ciphertext_absent` — a `passport` element with no `data` field.
        $reached = array_merge($reached, self::codesOf(
            $this->coverage($this->botApiUpdate(['passport'], dropData: true), ['passport']),
        ));

        // `credentials_absent` — an element with `data` but no §4 pair.
        $reached = array_merge($reached, self::codesOf(
            $this->coverage($this->botApiUpdate(['passport'], dropCredentials: true), ['passport']),
        ));

        // `encoding_invalid` — a raw `secureData.data_hash` that is not 32
        // bytes. The wrong-LENGTH case, not the wrong-VALUE one: a 32-byte
        // digest that does not match is a tamper (below), while a field that
        // is not a digest at all is the raw-vs-base64 trap and must be named
        // at the boundary.
        $reached = array_merge($reached, self::codesOf($this->mtProtoCoverage(['driver_license'], 'short-digest')));

        // `integrity_failed` — a well-formed 32-byte digest that no longer
        // matches its bytes. The element is still PRESENT, which is the point:
        // present-but-tampered reads as a named refusal, not as a document and
        // not as an absence.
        $reached = array_merge($reached, self::codesOf($this->mtProtoCoverage(['driver_license'], 'tampered')));

        // `file_hash_unavailable` — the Bot API `PassportFile` shape.
        $reached = array_merge($reached, self::codesOf(
            $this->coverage($this->botApiUpdate(['passport']), ['passport']),
        ));

        // `wire_unavailable` — no download seam bound.
        $reached = array_merge($reached, self::codesOf($this->mtProtoCoverage(['passport'], 'no-wire')));

        // `download_failed` — the seam is bound and Telegram refuses.
        $reached = array_merge($reached, self::codesOf($this->mtProtoCoverage(['passport'], 'refused')));

        // `file_too_large` — §4's 10 MB ceiling, refused before download.
        $reached = array_merge($reached, self::codesOf($this->mtProtoCoverage(['passport'], 'oversized')));

        self::assertSame(
            self::sortedUnique(PassportDocumentRefusal::CODES),
            self::sortedUnique($reached),
            'the refusal vocabulary is closed: every declared code must be reachable and nothing may be reachable that is not declared.',
        );
        self::assertSame(
            count(PassportDocumentRefusal::CODES),
            count(array_unique(PassportDocumentRefusal::CODES)),
            'a declared refusal code may not appear twice — a duplicate reads as two named refusals where there is one.',
        );
    }

    // ── the nested decrypt, really running ──────────────────────────────

    /** The §4 `data` decrypt is reached with real bytes and real crypto. */
    public function test_the_nested_data_decrypt_really_runs_on_the_production_path(): void
    {
        $update = $this->botApiUpdate(['personal_details']);
        $payload = $this->payload($update);
        $source = PassportDocumentSource::fromUpdate($update, $payload->secureData());

        $envelope = $source->dataEnvelope('personal_details');
        self::assertNotNull($envelope, 'the §4 DataCredentials envelope must be readable');
        self::assertSame(32, strlen($envelope->rawHash()), '§4 digests are 32 RAW bytes');
        self::assertNotSame('', $envelope->ciphertext());
        self::assertNotSame('', $envelope->rawSecret());

        $document = (new PassportDocumentResolver($this->privatePem))->resolveData('personal_details', $source);

        self::assertInstanceOf(PassportDocument::class, $document);
        self::assertSame(PassportDocument::CLASS_PERSONAL_DETAILS, $document->payloadClass);
        self::assertSame('Ada', $document->value('first_name'));
    }

    /**
     * `upload.getFile` + §4 `FileCredentials` over the MTProto seam — the
     * download half, end to end. The `secureFile` coordinates are read, the
     * method is dispatched with `inputSecureFileLocation`, and the bytes come
     * back decrypted.
     */
    public function test_the_file_download_and_decrypt_really_run_over_the_wire(): void
    {
        $jpeg = "\xFF\xD8\xFF\xE0".str_repeat('t', 64)."\xFF\xD9";
        $sealed = $this->factory->sealFileCredentials($jpeg);

        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_GET_FILE => [
                ['_' => 'upload.file', 'type' => 'storage.filePartial', 'mtime' => 0, 'bytes' => $sealed['content']],
            ],
        ]);

        $update = $this->mtProtoUpdate($sealed, 64);
        $source = PassportDocumentSource::fromUpdate($update, $this->payload($update)->secureData());

        $envelopes = $source->fileEnvelopes('passport', PassportDocumentRefusal::SLOT_FRONT_SIDE);
        self::assertCount(1, $envelopes);

        $document = (new PassportDocumentResolver($this->privatePem, $wire))->resolveFile($envelopes[0]);

        self::assertInstanceOf(PassportDocument::class, $document, 'the file must decrypt to a document, not a refusal');
        self::assertSame($jpeg, $document->bytes());

        $dispatch = $wire->firstDispatchOf(PassportWireContract::METHOD_GET_FILE);
        self::assertNotNull($dispatch, 'upload.getFile must actually have been dispatched');
        self::assertSame('inputSecureFileLocation', $dispatch['params']['location']['_']);
        self::assertSame(4242, $dispatch['params']['location']['id']);
        self::assertSame(777, $dispatch['params']['location']['access_hash']);
        self::assertSame(0, $dispatch['params']['offset']);
    }

    /** The download method is on the curated list, so it is reachable by name. */
    public function test_the_download_method_is_curated_and_carries_its_official_constructor_id(): void
    {
        self::assertSame('upload.getFile', PassportWireContract::METHOD_GET_FILE);
        self::assertSame('be5335be', PassportWireContract::OFFICIAL_IDS[PassportWireContract::METHOD_GET_FILE]);
        self::assertSame('90c894b5', PassportWireContract::OFFICIAL_IDS[PassportWireContract::METHOD_SET_SECURE_VALUE_ERRORS]);

        // The two ids are quoted from the layer-227 mirror this package ships.
        $mirror = file_get_contents(__DIR__.'/../../src/Schema/schema/sources/api_full.tl');
        self::assertIsString($mirror);
        self::assertStringContainsString('upload.getFile#be5335be', $mirror);
        self::assertStringContainsString('users.setSecureValueErrors#90c894b5', $mirror);
        self::assertStringContainsString('inputSecureFileLocation#cbc7ee28', $mirror);

        // AND the builders exist, so the methods are reachable through the
        // fluent surface and not only through this module's own constants.
        self::assertTrue(class_exists(Upload::class));
        self::assertTrue(class_exists(Users::class));
    }

    /**
     * `help.getFile` is NOT a Telegram method — it 404s on
     * `core.telegram.org/method/help.getFile` and is absent from both the
     * layer-229 catalogue and the layer-227 mirror. This test is the citation
     * that stops someone "fixing" the download back onto a 404, and it reads
     * the mirror rather than trusting the prose.
     */
    public function test_help_get_file_is_not_a_telegram_method_and_the_package_says_so(): void
    {
        $mirror = (string) file_get_contents(__DIR__.'/../../src/Schema/schema/sources/api_full.tl');
        $catalogue = (string) file_get_contents(__DIR__.'/../../src/Schema/schema/methods-mtproto.json');

        self::assertStringNotContainsString('help.getFile', $mirror, 'the layer-227 mirror does not declare it');
        self::assertStringNotContainsString('help.getFile', $catalogue, 'the layer-229 catalogue does not declare it');
        self::assertStringContainsString('upload.getFile#be5335be', $mirror, 'the real download method IS declared');

        // The reason must stay written down where the download is built, naming
        // both the method that was wrongly expected and the one that is real.
        $envelopeSource = (string) file_get_contents(__DIR__.'/../../src/Teleframe/Passport/PassportFileEnvelope.php');
        self::assertStringContainsString('help.getFile', $envelopeSource);
        self::assertStringContainsString('not a Telegram method', $envelopeSource);
        self::assertStringContainsString('upload.getFile', $envelopeSource);
    }

    // ── setPassportDataErrors / users.setSecureValueErrors ─────────────

    /** The Bot API `errors` array is built, in Telegram's own field names. */
    public function test_the_bot_api_errors_array_is_built_for_reportable_refusals(): void
    {
        // `tamperDigest` on the whole vocabulary: every §5 data type now refuses
        // `integrity_failed` while every FILE slot still refuses
        // `file_hash_unavailable`, so both report shapes are exercised at once.
        $update = $this->botApiUpdate(self::FULL_VOCABULARY, tamperDigest: true);
        $source = PassportDocumentSource::fromUpdate($update, $this->payload($update)->secureData());
        $coverage = $this->coverage($update, self::FULL_VOCABULARY);

        $errors = (new PassportErrorReporter)->botApiErrors($coverage, $source);

        self::assertNotSame([], $errors, 'a refused vocabulary must produce reportable errors');

        // A `data` refusal reports as PassportElementErrorDataField, carrying the
        // base64 of the very digest §4 checked — the handle Telegram matches a
        // re-submission against.
        $dataError = self::errorWithSource($errors, 'data');
        self::assertNotNull($dataError, 'a refused data slot must report as PassportElementErrorDataField');
        self::assertContains($dataError['type'], PassportErrorReporter::DATA_ERROR_TYPES);
        self::assertSame('data', $dataError['source']);
        self::assertNotSame('', $dataError['data_hash']);
        self::assertNotFalse(base64_decode((string) $dataError['data_hash'], true));

        // A FILE refusal with no per-file hash reports as Unspecified against
        // the ELEMENT hash — Telegram's documented mechanism for "this element
        // must change", and NOT a drop.
        $unspecified = self::errorWithSource($errors, 'unspecified');
        self::assertNotNull($unspecified, 'a Bot API file refusal must report as Unspecified, not vanish');
        self::assertSame('passport', $unspecified['type']);
        self::assertNotSame('', $unspecified['element_hash']);
        self::assertNotFalse(base64_decode((string) $unspecified['element_hash'], true));

        // A §6 claim that never arrived is NOT reported: the user cannot "fix"
        // a field Telegram chose not to verify.
        foreach ($errors as $error) {
            self::assertNotContains($error['type'], ['email', 'phone_number']);
        }
    }

    /** `users.setSecureValueErrors` dispatches with `inputUserSelf`. */
    public function test_the_mtproto_secure_value_errors_dispatch_over_the_seam(): void
    {
        $sealed = $this->factory->sealFileCredentials('x');
        $update = $this->mtProtoUpdate($sealed, 64);
        $source = PassportDocumentSource::fromUpdate($update, $this->payload($update)->secureData());
        $coverage = PassportDocumentCoverage::build(
            ['passport'],
            $this->payload($update),
            $source,
            new PassportDocumentResolver($this->privatePem),
        );

        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_SET_SECURE_VALUE_ERRORS => [['_' => 'boolTrue']],
        ]);

        self::assertTrue((new PassportErrorReporter($wire))->report($coverage, $source));

        $dispatch = $wire->firstDispatchOf(PassportWireContract::METHOD_SET_SECURE_VALUE_ERRORS);
        self::assertNotNull($dispatch);
        self::assertSame('inputUserSelf', $dispatch['params']['id']['_']);

        // §7 slot order, so `data` precedes `front_side`; the FILE error is the
        // one that proves the per-file hash was read, so it is looked up BY
        // constructor rather than by index.
        $frontSide = self::secureValueError($dispatch['params']['errors'], 'secureValueErrorFrontSide');
        self::assertNotNull($frontSide, 'the front_side refusal must be reported');
        self::assertSame('secureValueTypePassport', $frontSide['type']);
        self::assertSame(32, strlen((string) $frontSide['file_hash']));

        // The `data` refusal on the same element is reported too, with the §4
        // `data_hash` and the field name, in that constructor's own shape.
        $data = self::secureValueError($dispatch['params']['errors'], 'secureValueErrorData');
        self::assertNotNull($data);
        self::assertSame('secureValueTypePassport', $data['type']);
        self::assertSame(32, strlen((string) $data['data_hash']));
        self::assertSame('data', $data['field']);
    }

    /** No seam bound means "not reported", said honestly — never a silent yes. */
    public function test_reporting_without_a_seam_returns_false_rather_than_claiming_success(): void
    {
        $update = $this->botApiUpdate(['passport']);
        $source = PassportDocumentSource::fromUpdate($update, $this->payload($update)->secureData());
        $coverage = $this->coverage($update, ['passport']);

        $reporter = new PassportErrorReporter;

        self::assertFalse($reporter->report($coverage, $source));
        self::assertNotSame([], $reporter->botApiErrors($coverage, $source));
    }

    /** A fully-successful document is never reported as an error. */
    public function test_the_error_reporter_never_reports_a_successful_document(): void
    {
        $update = $this->botApiUpdate(['personal_details']);
        $source = PassportDocumentSource::fromUpdate($update, $this->payload($update)->secureData());
        $coverage = $this->coverage($update, ['personal_details']);

        $reporter = new PassportErrorReporter;

        self::assertSame([], $reporter->botApiErrors($coverage, $source));
        self::assertSame([], $reporter->secureValueErrors($coverage, $source));
        self::assertFalse($reporter->report($coverage, $source));
    }

    // ── the chain wiring ───────────────────────────────────────────────

    /**
     * THE WIRING. The table is not something a caller opts into — the login
     * chain builds it on every run and hands it back on the `LoginResult`, so
     * a host gets it whether or not it knows to ask.
     *
     * `allowed_types` here deliberately carries NO `personal_details`, because
     * the shipped `PassportValueValidator` requires a plain `first_name` at
     * `secure_data.personal_details` — a coordinate upstream does not write,
     * because it lives inside the §4 `SecureData.data` this layer now decrypts.
     * See the S4 report: that is a pre-existing gate, unchanged here, and it is
     * LOUD rather than silent, so it does not break the rule this test pins.
     */
    public function test_the_login_chain_carries_the_document_table_on_every_run(): void
    {
        PassportDocumentChainUser::$resolved = PassportDocumentChainUser::withId(4242);

        $nonces = new MemoryNonceRegistry(900);
        $nonce = $nonces->issue(4242);

        $update = $this->botApiUpdate(['email', 'driver_license']);
        $update['message']['passport_data']['credentials'] = $this->factory->seal([
            'secure_data' => $this->secureDataOf($update),
            'nonce' => $nonce,
        ]);

        $service = new PassportLoginService(
            config: new PassportConfig([
                'enabled' => true,
                'private_key_path' => $this->pemFile,
                'account_id' => 7,
                'allowed_types' => ['email', 'driver_license'],
            ]),
            keys: new PassportKeyLoader($this->pemFile),
            nonces: $nonces,
            identity: new SessionIdentityResolver(PassportDocumentChainUser::class),
            writer: new MemoryBindingWriter,
        );

        $result = $service->login($update, 4242);

        $coverage = $result->documents;
        self::assertNotNull($coverage, 'the chain MUST carry a document table — an absent table is the defect');
        self::assertTrue($coverage->isComplete());

        // `email` really arrived (§6 plaintext) and `driver_license` really
        // decrypted (§4) — both off the SAME update, on the SAME run.
        self::assertInstanceOf(
            PassportDocument::class,
            self::rowFor($coverage, 'email', PassportDocumentRefusal::SLOT_PLAIN_DATA),
        );
        self::assertInstanceOf(
            PassportDocument::class,
            self::rowFor($coverage, 'driver_license', PassportDocumentRefusal::SLOT_DATA),
        );

        // `driver_license` also has file slots §7 grants it, and the Bot API
        // shape cannot decrypt them — so those are NAMED, not absent.
        foreach ([PassportDocumentRefusal::SLOT_FRONT_SIDE, PassportDocumentRefusal::SLOT_REVERSE_SIDE] as $slot) {
            self::assertTrue(
                self::rowFor($coverage, 'driver_license', $slot)->is(PassportDocumentRefusal::FILE_HASH_UNAVAILABLE),
                'driver_license.'.$slot.' must be named, not absent.',
            );
        }

        // And the result's public shape carries the table WITHOUT any personal
        // data in it — a login result gets serialised into logs.
        $rendered = $result->toArray();
        self::assertIsArray($rendered['documents']);
        self::assertStringNotContainsString('ada@example.test', json_encode($rendered) ?: '');
        self::assertStringNotContainsString('+15550000000', json_encode($rendered) ?: '');
    }

    /**
     * The `secure_data` index of a fixture update — the §4 credentials table
     * the envelope was sealed with, read back so the re-seal above carries the
     * SAME pairs (a fresh `botApiUpdate()` would mint new random secrets and
     * the elements' ciphertexts would no longer match).
     *
     * @param  array<string, mixed>  $update
     * @return array<string, mixed>
     */
    private function secureDataOf(array $update): array
    {
        $credentials = EncryptedCredentials::fromUpdate($update);
        self::assertNotNull($credentials);

        $decrypted = PassportDecryptor::decryptCredentials(
            $credentials->data,
            $credentials->secret,
            $this->privatePem,
            $credentials->hash,
        );

        return $decrypted['secure_data'] ?? [];
    }

    // ── the shipped default ────────────────────────────────────────────

    /**
     * THE FIVE FILE-ONLY TYPES REALLY WERE READ.
     *
     * This test exists because they were NOT, once. `readBotApiElement()`
     * returned early when an element carried no `data`, and the file loops sat
     * below that return — so §7's five types that have `files`/`translation` and
     * NO `data` field were precisely the five the walk skipped, and every scan
     * on a real utility bill came out `slot_absent` on a response that had sent
     * it. That is "a half-walk is how the previous defect shipped green", one
     * level down.
     *
     * The distinguishing assertion is the CODE, not the row count: the element
     * arrived WITH a file, so the refusal must be `file_hash_unavailable` (this
     * shape cannot decrypt it) and must NOT be `slot_absent` (nothing was
     * there). `slot_absent` here would be the walk reporting a file it never
     * looked at.
     */
    public function test_a_file_only_types_scans_are_read_and_not_skipped(): void
    {
        $coverage = $this->coverage($this->botApiUpdate(self::FULL_VOCABULARY), PassportDocument::FILE_ONLY_TYPES);

        self::assertTrue($coverage->isComplete());

        foreach (PassportDocument::FILE_ONLY_TYPES as $type) {
            foreach ([PassportDocumentRefusal::SLOT_FILES, PassportDocumentRefusal::SLOT_TRANSLATION] as $slot) {
                $row = self::rowFor($coverage, $type, $slot);

                self::assertInstanceOf(PassportDocumentRefusal::class, $row, $type.'.'.$slot);
                self::assertTrue(
                    $row->is(PassportDocumentRefusal::FILE_HASH_UNAVAILABLE),
                    $type.'.'.$slot.' carried a file, so it must be refused for LACKING A HASH, '
                    .'not reported as an absent slot — that would mean the walk never read it.',
                );
            }
        }
    }

    /** The shipped default allow-list — the one a host gets without asking. */
    public function test_the_shipped_default_allow_list_is_covered_too(): void
    {
        $defaults = (new PassportConfig)->allowedTypes();

        self::assertSame(['personal_details', 'email', 'phone'], $defaults);

        $coverage = $this->coverage($this->botApiUpdate(self::FULL_VOCABULARY), $defaults);

        self::assertTrue($coverage->isComplete());

        foreach ($defaults as $type) {
            self::assertNotSame([], $coverage->for($type));
        }

        // AND all three produce DOCUMENTS, not merely rows. `phone` is the §8
        // alias of `phone_number`, and it is the spelling the SHIPPED DEFAULT
        // uses: a host that never edited its config asked for a type Telegram
        // does not name, and the response side had no translation for it while
        // `PassportConfig::expandScopeAlias()` translated it on the request
        // side. Before this alias the default produced a NAMED REFUSAL for
        // `phone` on every single login — correct as an absence, wrong as a
        // product, because Telegram had sent the claim.
        foreach ($defaults as $type) {
            $row = self::rowFor($coverage, $type, self::firstSlotOf($coverage, $type));

            self::assertInstanceOf(
                PassportDocument::class,
                $row,
                'the shipped default asks for "'.$type.'", so "'.$type.'" must resolve to a document.',
            );
        }

        // The two spellings must agree with the request side, or a host would
        // ask for one thing and be told about another.
        $requestScope = (new PassportConfig)->requestScope();

        self::assertContains('phone_number', $requestScope['data']);
        self::assertSame(
            'phone_number',
            PassportDocumentSource::resolveAlias('phone'),
            'the request alias and the response alias must name the same element.',
        );
    }

    /**
     * @param  list<string>  $allowedTypes
     */
    private static function firstSlotOf(PassportDocumentCoverage $coverage, string $type): string
    {
        $rows = $coverage->for($type);
        self::assertNotSame([], $rows, 'no rows at all for '.$type);

        return $rows[0]->slot;
    }

    // ── fixtures ───────────────────────────────────────────────────────

    /**
     * @param  list<string>  $allowedTypes
     */
    private function coverage(array $update, array $allowedTypes): PassportDocumentCoverage
    {
        $payload = $this->payload($update);

        return PassportDocumentCoverage::build(
            $allowedTypes,
            $payload,
            PassportDocumentSource::fromUpdate($update, $payload->secureData()),
            new PassportDocumentResolver($this->privatePem),
        );
    }

    /**
     * An MTProto coverage with a named wire posture, so each download refusal
     * can be produced on demand.
     *
     * @param  list<string>  $allowedTypes
     */
    private function mtProtoCoverage(array $allowedTypes, string $posture): PassportDocumentCoverage
    {
        $sealed = null;

        if ($posture === 'short-digest') {
            $update = $this->mtProtoUpdateWithDigest(str_repeat("\x00", 31));
        } elseif ($posture === 'tampered') {
            $data = $this->factory->sealDataCredentials((string) json_encode(['document_no' => '7070707']));
            $update = $this->mtProtoUpdateWithDigest(strrev($data['hash']));
        } else {
            $sealed = $this->factory->sealFileCredentials('file-bytes');
            $update = $this->mtProtoUpdate($sealed, $posture === 'oversized' ? self::OVERSIZED_SIZE : 64);
        }

        $payload = $this->payload($update);
        $source = PassportDocumentSource::fromUpdate($update, $payload->secureData());

        /**
         * The two `data`-only postures bind NO wire on purpose: their rows are
         * `data` refusals, and a `front_side` row would be a different fact.
         * Listing them explicitly rather than falling through to `default`
         * keeps a typo'd posture from silently building a wire it never needs.
         */
        $wire = match ($posture) {
            'no-wire', 'oversized', 'short-digest', 'tampered' => null,
            'refused' => new MemoryPassportWire([
                PassportWireContract::METHOD_GET_FILE => [new \RuntimeException('FILE_ID_INVALID')],
            ]),
            default => new MemoryPassportWire([
                PassportWireContract::METHOD_GET_FILE => [
                    [
                        '_' => 'upload.file',
                        'mtime' => 0,
                        'bytes' => self::sealedContent($sealed),
                    ],
                ],
            ]),
        };

        return PassportDocumentCoverage::build(
            $allowedTypes,
            $payload,
            $source,
            new PassportDocumentResolver($this->privatePem, $wire),
        );
    }

    /**
     * The sealed file's ciphertext, asserted rather than assumed — so a
     * mis-ordered posture above fails here instead of raising a PHP warning
     * about an undefined variable inside a `match` arm.
     *
     * @param  array<string, mixed>|null  $sealed
     */
    private static function sealedContent(?array $sealed): string
    {
        self::assertIsArray($sealed, 'this posture must seal a file before dispatching upload.getFile');
        self::assertArrayHasKey('content', $sealed);

        return (string) $sealed['content'];
    }

    /**
     * @param  array<string, mixed>  $update
     */
    private function payload(array $update): PassportPayload
    {
        $credentials = EncryptedCredentials::fromUpdate($update);
        self::assertNotNull($credentials, 'the fixture must be a well-formed passport update');

        return new PassportPayload(
            PassportDecryptor::decryptCredentials(
                $credentials->data,
                $credentials->secret,
                $this->privatePem,
                $credentials->hash,
            ),
            $credentials->claims,
        );
    }

    /**
     * A REAL Bot API update: `PassportData.data[]` elements of §6 and §7, and
     * `credentials` sealed against this test's keypair.
     *
     * Per upstream, `Credentials` carries "the data_hash and data_secret from
     * each element", so `secure_data[type]` is the §4 credentials index —
     * base64, because it lives inside a JSON string — while the CIPHERTEXT
     * travels on the element's own `data`. That split is what this fixture
     * reproduces, and it is the split that makes a reader which treats both as
     * raw (or both as base64) fail on real Telegram data.
     *
     * @param  list<string>  $types
     * @return array<string, mixed>
     */
    private function botApiUpdate(
        array $types,
        bool $dropData = false,
        bool $dropCredentials = false,
        bool $tamperDigest = false,
    ): array {
        $secureData = [];
        $elements = [];

        foreach ($types as $type) {
            $element = ['type' => $type, 'hash' => base64_encode(random_bytes(32))];

            if ($type === 'email') {
                $element['email'] = 'ada@example.test';
                $elements[] = $element;

                continue;
            }

            if ($type === 'phone_number') {
                $element['phone_number'] = '+15550000000';
                $elements[] = $element;

                continue;
            }

            if (in_array($type, PassportDocument::FILE_ONLY_TYPES, true)) {
                $element['files'] = [$this->passportFile()];
                $element['translation'] = [$this->passportFile()];
                $elements[] = $element;

                continue;
            }

            if (! $dropData) {
                $sealed = $this->factory->sealDataCredentials((string) json_encode($this->documentPayload($type)));
                $element['data'] = base64_encode($sealed['data']);

                if (! $dropCredentials) {
                    $hash = $tamperDigest ? strrev($sealed['hash']) : $sealed['hash'];
                    $secureData[$type] = [
                        'data_hash' => base64_encode($hash),
                        'secret' => base64_encode($sealed['secret']),
                    ];
                }
            }

            if (in_array($type, ['passport', 'internal_passport'], true)) {
                $element['front_side'] = $this->passportFile();
                $element['selfie'] = $this->passportFile();
            }

            if (in_array($type, ['driver_license', 'identity_card'], true)) {
                $element['front_side'] = $this->passportFile();
                $element['reverse_side'] = $this->passportFile();
                $element['selfie'] = $this->passportFile();
            }

            $elements[] = $element;
        }

        return [
            'update_id' => 1,
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 4242, 'is_bot' => false],
                'passport_data' => [
                    'data' => $elements,
                    'credentials' => $this->factory->seal([
                        'secure_data' => $secureData,
                        'nonce' => 'nonce-for-the-document-coverage-gate',
                    ]),
                ],
            ],
        ];
    }

    /**
     * The three §4 payload classes upstream names, one per §5 group.
     *
     * @return array<string, mixed>
     */
    private function documentPayload(string $type): array
    {
        return match (PassportDocument::DATA_PAYLOAD_CLASSES[$type] ?? '') {
            PassportDocument::CLASS_PERSONAL_DETAILS => [
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'birth_date' => '10.12.1815',
                'gender' => 'female',
                'country_code' => 'GB',
            ],
            PassportDocument::CLASS_ID_DOCUMENT => [
                'document_no' => '7070707',
                'expiry_date' => '01.01.2031',
                'issuing_country' => 'GB',
            ],
            default => [
                'street_line1' => '12 Example Street',
                'city' => 'London',
                'country_code' => 'GB',
            ],
        };
    }

    /**
     * @return array{file_id: string, file_unique_id: string, file_size: int, file_date: int}
     */
    private function passportFile(): array
    {
        return [
            'file_id' => 'passport-file-id',
            'file_unique_id' => 'passport-file-unique-id',
            'file_size' => 2048,
            'file_date' => 1700000000,
        ];
    }

    /**
     * The MTProto shape with a COMPLETE `secureFile#7d09c27e` — the only shape
     * that carries §4 `FileCredentials` AND an `upload.getFile` location. Its
     * `SecureValue.data` uses a data_hash that is deliberately wrong, so a
     * fixture for the FILE slots cannot accidentally double as a good `data`
     * fixture.
     *
     * @param  array{content: string, hash: string, secret: string}  $sealed
     * @return array<string, mixed>
     */
    private function mtProtoUpdate(array $sealed, int $declaredSize): array
    {
        return $this->mtProtoUpdateWith([
            '_' => 'secureValue',
            'type' => ['_' => 'secureValueTypePassport'],
            'data' => [
                '_' => 'secureData',
                'data' => random_bytes(64),
                'data_hash' => str_repeat("\x00", 31)."\x01",
                'secret' => str_repeat("\x02", 256),
            ],
            'front_side' => [
                '_' => 'secureFile',
                'id' => 4242,
                'access_hash' => 777,
                'size' => $declaredSize,
                'dc_id' => 2,
                'date' => 1700000000,
                'file_hash' => $sealed['hash'],
                'secret' => $sealed['secret'],
            ],
        ], []);
    }

    /**
     * The MTProto shape with a `SecureValue.data.data_hash` the caller chose,
     * so both raw/base64 failures can be produced from one fixture: a digest
     * that is not 32 bytes (`encoding_invalid`), and one that is 32 bytes but
     * no longer the digest of the bytes beside it (`integrity_failed`).
     *
     * @return array<string, mixed>
     */
    private function mtProtoUpdateWithDigest(string $rawDataHash): array
    {
        $sealed = $this->factory->sealDataCredentials((string) json_encode(['document_no' => '7070707']));

        return $this->mtProtoUpdateWith([
            '_' => 'secureValue',
            'type' => ['_' => 'secureValueTypeDriverLicense'],
            'data' => [
                '_' => 'secureData',
                'data' => $sealed['data'],
                'data_hash' => $rawDataHash,
                'secret' => $sealed['secret'],
            ],
        ], [
            'driver_license' => [
                'data_hash' => base64_encode($sealed['hash']),
                'secret' => base64_encode($sealed['secret']),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $value
     * @param  array<string, mixed>  $secureData
     * @return array<string, mixed>
     */
    private function mtProtoUpdateWith(array $value, array $secureData): array
    {
        return [
            'update_id' => 2,
            '_' => 'updateNewMessage',
            'message' => [
                '_' => 'messageService',
                'id' => 1,
                'peer_id' => ['_' => 'peerUser', 'user_id' => 4242],
                'action' => [
                    '_' => 'messageActionSecureValuesSentMe',
                    'values' => [$value],
                    'credentials' => $this->factory->seal([
                        'secure_data' => $secureData,
                        'nonce' => 'nonce-for-the-mtproto-document-gate',
                    ]),
                ],
            ],
        ];
    }

    // ── helpers ────────────────────────────────────────────────────────

    private static function rowFor(PassportDocumentCoverage $coverage, string $type, string $slot)
    {
        foreach ($coverage->for($type) as $row) {
            if ($row->slot === $slot) {
                return $row;
            }
        }

        self::fail('no '.$type.'.'.$slot.' row in the coverage table');
    }

    /**
     * @return list<string>
     */
    private static function codesOf(PassportDocumentCoverage $coverage): array
    {
        $codes = [];
        foreach ($coverage->refusals() as $refusal) {
            $codes[] = $refusal->code;
        }

        return $codes;
    }

    /**
     * @param  list<string>  $codes
     * @return list<string>
     */
    private static function sortedUnique(array $codes): array
    {
        $codes = array_values(array_unique($codes));
        sort($codes);

        return $codes;
    }

    /**
     * @param  list<array<string, mixed>>  $errors
     * @return array<string, mixed>|null
     */
    private static function errorWithSource(array $errors, string $source): ?array
    {
        foreach ($errors as $error) {
            if (($error['source'] ?? null) === $source) {
                return $error;
            }
        }

        return null;
    }

    /**
     * A `secureValueError*` body BY CONSTRUCTOR — the MTProto error family has
     * nine members with nine different field sets, so an index would be a
     * silent coupling to the §7 slot order.
     *
     * @param  list<array<string, mixed>>  $errors
     * @return array<string, mixed>|null
     */
    private static function secureValueError(array $errors, string $constructor): ?array
    {
        foreach ($errors as $error) {
            if (($error['_'] ?? null) === $constructor) {
                return $error;
            }
        }

        return null;
    }
}

/**
 * PassportDocumentChainUser — the chain-wiring test's identity seam.
 *
 * `SessionIdentityResolver` needs a class that is a real Eloquent Model with a
 * DB-free query surface, so `query()->where()->first()` is the whole
 * implementation. Distinct from `PassportLoginServiceTest`'s `FakePassportUser`
 * on purpose: that one is seeded by a static and asserted on by that suite,
 * and sharing it would couple the document gate's liveness to another file's
 * setup.
 */
final class PassportDocumentChainUser extends Model
{
    /** @var list<array{key: string, value: int}> */
    public static array $lookups = [];

    public static ?self $resolved = null;

    public $timestamps = false;

    protected $guarded = [];

    public static function withId(int $id): self
    {
        $user = new self;
        $user->id = $id;

        self::$lookups = [];
        self::$resolved = $user;

        return $user;
    }

    public static function query(): static
    {
        return new self;
    }

    public function where(string $key, mixed $value): static
    {
        self::$lookups[] = ['key' => $key, 'value' => (int) $value];

        return $this;
    }

    public function first(): ?static
    {
        return self::$resolved;
    }
}
