<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use PHPUnit\Framework\TestCase;
use MeRezaRezaei\Teleframe\Schema\Generator\ModelGenerator;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;

final class ModelGeneratorTest extends TestCase
{
    private const TYPES_HEADER = "---types---\n";

    /** @return array<string,string> */
    private static function generate(): array
    {
        $scheme = TlParser::parseFile(__DIR__ . '/fixtures/mini.tl', 0, strict: true);
        return (new ModelGenerator())->generate($scheme);
    }

    /** @return array<string,string> */
    private static function generateFromString(string $tl): array
    {
        $scheme = TlParser::parseString(self::TYPES_HEADER . $tl, 0, '<test>');
        return (new ModelGenerator())->generate($scheme);
    }

    public function test_anchor_model(): void
    {
        $files = self::generate();
        self::assertArrayHasKey('TlUser.php', $files);
        self::assertStringContainsString('final class TlUser extends TlAnchorModel', $files['TlUser.php']);
        self::assertStringContainsString("protected \$table = 'tl_user';", $files['TlUser.php']);
    }

    public function test_ctor_model_casts(): void
    {
        $files = self::generate();
        self::assertArrayHasKey('TlUserUserEmpty.php', $files);
        $model = $files['TlUserUserEmpty.php'];
        self::assertStringContainsString('final class TlUserUserEmpty extends TlInstanceModel', $model);
        self::assertStringContainsString("protected \$table = 'tl_user_user_empty';", $model);
        self::assertStringContainsString("'tl_id' => 'int'", $model);
        self::assertStringContainsString('use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;', $model);
    }

    public function test_vector_child_method_and_model(): void
    {
        $files = self::generate();
        self::assertArrayHasKey('TlMessagesMessagesMessages.php', $files);
        $ctor = $files['TlMessagesMessagesMessages.php'];
        self::assertStringContainsString('public function messages(): HasMany', $ctor);
        self::assertStringContainsString('$this->tlChild(TlMessagesMessagesMessagesMessages::class);', $ctor);
        self::assertArrayHasKey('TlMessagesMessagesMessagesMessages.php', $files);
        $child = $files['TlMessagesMessagesMessagesMessages.php'];
        self::assertStringContainsString("protected \$table = 'tl_messages_messages_messages__messages';", $child);
        self::assertStringContainsString('public $timestamps = false;', $child);
    }

    public function test_deterministic(): void
    {
        self::assertSame(self::generate(), self::generate());
    }

    public function test_generated_files_are_valid_php(): void
    {
        foreach (self::generate() as $name => $content) {
            $tmp = tempnam(sys_get_temp_dir(), 'tlmdl') . '.php';
            file_put_contents($tmp, $content);
            exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $out, $code);
            unlink($tmp);
            self::assertSame(0, $code, "php -l failed for {$name}: " . implode("\n", $out));
        }
    }

    // --- Task 2.1: belongsTo for object-ref params ---

    public function test_object_ref_params_generate_belongsTo_methods(): void
    {
        $models = self::generateFromString(
            "message#dead0001 id:int media:flags.9?MessageMedia fwd_from:flags.2?MessageFwdHeader = Message;\n"
            . "mediaEmpty#dead0002 = MessageMedia;\n"
            . "headerEmpty#dead0003 = MessageFwdHeader;\n",
        );
        $instance = $models['TlMessageMessage.php'];
        self::assertStringContainsString('public function media(): BelongsTo', $instance);
        self::assertStringContainsString("->belongsTo(TlMessageMedia::class, 'media')", $instance);
        self::assertStringContainsString('use MeRezaRezaei\\Teleframe\\Schema\\Generated\\Models\\TlMessageMedia;', $instance);
        self::assertStringContainsString('public function fwdFrom(): BelongsTo', $instance);
        self::assertStringContainsString("->belongsTo(TlMessageFwdHeader::class, 'fwd_from')", $instance);
        self::assertStringContainsString('use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;', $instance);
    }

    public function test_belongsto_import_not_emitted_when_no_object_refs(): void
    {
        $models = self::generateFromString(
            "userEmpty#cafe0001 id:long = User;\n",
        );
        $instance = $models['TlUserUserEmpty.php'];
        self::assertStringNotContainsString('BelongsTo', $instance);
    }

    // --- Task 2.3: reverse hasMany on anchors ---

    public function test_anchor_models_get_reverse_has_many_for_incoming_refs(): void
    {
        $models = self::generateFromString(
            "message#dead0001 id:int media:flags.9?MessageMedia = Message;\n"
            . "mediaEmpty#dead0002 = MessageMedia;\n",
        );
        self::assertStringContainsString('public function media(): HasMany', $models['TlMessageMedia.php']);
        self::assertStringContainsString("->hasMany(TlMessageMessage::class, 'media')", $models['TlMessageMedia.php']);
        self::assertStringContainsString('use Illuminate\\Database\\Eloquent\\Relations\\HasMany;', $models['TlMessageMedia.php']);
        self::assertStringContainsString('use MeRezaRezaei\\Teleframe\\Schema\\Generated\\Models\\TlMessageMessage;', $models['TlMessageMedia.php']);
    }

    public function test_reverse_has_many_deduplicates_same_param_name(): void
    {
        $models = self::generateFromString(
            "message#dead0001 id:int media:flags.9?MessageMedia = Message;\n"
            . "post#dead0002 id:int media:flags.1?MessageMedia = Post;\n"
            . "mediaEmpty#dead0003 = MessageMedia;\n",
        );
        $anchor = $models['TlMessageMedia.php'];
        self::assertStringContainsString("->hasMany(TlMessageMessage::class, 'media')", $anchor);
        self::assertStringContainsString("->hasMany(TlPostPost::class, 'media')", $anchor);
    }

    public function test_anchor_without_incoming_refs_has_no_reverse_hasmany(): void
    {
        $models = self::generateFromString(
            "userEmpty#cafe0001 id:long = User;\n",
        );
        self::assertStringNotContainsString('HasMany', $models['TlUser.php']);
    }

    // --- Task 2.2: PeerResolution trait import ---

    public function test_peer_ref_constructor_emits_peer_resolution_trait(): void
    {
        $models = self::generateFromString(
            "message#dead0001 id:int peer_id:Peer = Message;\n"
            . "userPeer#dead0002 user_id:long = Peer;\n"
            . "chatPeer#dead0003 chat_id:long = Peer;\n",
        );
        $instance = $models['TlMessageMessage.php'];
        self::assertStringContainsString('use MeRezaRezaei\\Teleframe\\Schema\\Eloquent\\PeerResolution;', $instance);
    }

    public function test_peer_ref_does_not_generate_belongsto(): void
    {
        $models = self::generateFromString(
            "message#dead0001 id:int peer_id:Peer = Message;\n"
            . "userPeer#dead0002 user_id:long = Peer;\n",
        );
        $instance = $models['TlMessageMessage.php'];
        self::assertStringNotContainsString('belongsTo', $instance);
        self::assertStringNotContainsString('BelongsTo', $instance);
    }

    // --- Task 2.4: AccountScoped on every model ---

    public function test_account_scoped_trait_on_anchor_models(): void
    {
        $models = self::generateFromString(
            "userEmpty#cafe0001 id:long = User;\n",
        );
        $anchor = $models['TlUser.php'];
        self::assertStringContainsString('use MeRezaRezaei\\Teleframe\\Schema\\Eloquent\\AccountScoped;', $anchor);
        self::assertStringContainsString('use AccountScoped;', $anchor);
    }

    public function test_account_scoped_trait_on_ctor_models(): void
    {
        $models = self::generateFromString(
            "userEmpty#cafe0001 id:long = User;\n",
        );
        $ctor = $models['TlUserUserEmpty.php'];
        self::assertStringContainsString('use MeRezaRezaei\\Teleframe\\Schema\\Eloquent\\AccountScoped;', $ctor);
        self::assertStringContainsString('use AccountScoped;', $ctor);
    }

    public function test_account_scoped_trait_on_child_models(): void
    {
        $models = self::generateFromString(
            "message#dead0001 id:int tags:Vector<string> = Message;\n",
        );
        foreach ($models as $name => $content) {
            if (str_contains($content, 'public $timestamps = false;')) {
                self::assertStringContainsString('use MeRezaRezaei\\Teleframe\\Schema\\Eloquent\\AccountScoped;', $content, "AccountScoped import missing on child {$name}");
                self::assertStringContainsString('use AccountScoped;', $content, "use AccountScoped missing on child {$name}");
                return;
            }
        }
        self::fail('No child model found in generated output');
    }

    // --- Determinism across generate() calls ---

    public function test_deterministic_across_generate_calls(): void
    {
        $tl = "message#dead0001 id:int media:flags.9?MessageMedia peer_id:Peer = Message;\n"
            . "mediaEmpty#dead0002 = MessageMedia;\n"
            . "userPeer#dead0003 user_id:long = Peer;\n";
        $first = self::generateFromString($tl);
        $second = self::generateFromString($tl);
        self::assertSame(array_keys($first), array_keys($second));
        foreach ($first as $key => $content) {
            self::assertSame($content, $second[$key], "Output differs for {$key}");
        }
    }

    // --- Generated PHP validity for inline-scheme output ---

    public function test_generated_files_from_inline_scheme_are_valid_php(): void
    {
        $tl = "message#dead0001 id:int media:flags.9?MessageMedia peer_id:Peer = Message;\n"
            . "mediaEmpty#dead0002 = MessageMedia;\n"
            . "userPeer#dead0003 user_id:long = Peer;\n";
        foreach (self::generateFromString($tl) as $name => $content) {
            $tmp = tempnam(sys_get_temp_dir(), 'tlmdl') . '.php';
            file_put_contents($tmp, $content);
            exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $out, $code);
            unlink($tmp);
            self::assertSame(0, $code, "php -l failed for {$name}: " . implode("\n", $out));
        }
    }

    // --- Duplicate-use guard (round-1 fix) ---

    /**
     * The real crash: `user` holds two ref params to PeerColor, so the old
     * import assembly emitted `use ...TlPeerColor;` twice in one file
     * ("name is already in use"). Each FQCN must appear at most once.
     */
    public function test_multi_ref_params_to_same_base_emit_each_fqcn_once(): void
    {
        $models = self::generateFromString(
            "user#dead0001 id:long color:PeerColor profile_color:PeerColor = User;\n"
            . "peerColor#dead0002 color_id:int = PeerColor;\n",
        );
        $instance = $models['TlUserUser.php'];
        self::assertStringContainsString('public function color(): BelongsTo', $instance);
        self::assertStringContainsString('public function profileColor(): BelongsTo', $instance);
        self::assertSame(
            1,
            substr_count($instance, 'use MeRezaRezaei\\Teleframe\\Schema\\Generated\\Models\\TlPeerColor;'),
            'TlPeerColor must be imported exactly once in TlUserUser.php',
        );
    }

    /**
     * Same ref base appears BOTH as a forward belongsTo (ctor) and as a
     * reverse hasMany origin (anchor) across the scheme: every emitted file
     * must still import each FQCN at most once.
     */
    public function test_each_fqcn_imported_at_most_once_per_file(): void
    {
        $models = self::generateFromString(
            "message#dead0001 id:int media:MessageMedia reply_to:Message = Message;\n"
            . "mediaEmpty#dead0002 = MessageMedia;\n"
            . "mediaPhoto#dead0003 media:Message = MessageMedia;\n"
            . "user#dead0004 id:long color:PeerColor profile_color:PeerColor = User;\n"
            . "peerColor#dead0005 color_id:int = PeerColor;\n",
        );
        foreach ($models as $name => $content) {
            foreach (self::importsOf($content) as $fqcn => $count) {
                self::assertSame(
                    1,
                    $count,
                    "duplicate use import {$fqcn} in {$name} (count {$count})",
                );
            }
        }
    }

    /**
     * Top-level `use FQCN;` lines of a generated file.
     *
     * @return array<string,int> fqcn => occurrence count
     */
    private static function importsOf(string $content): array
    {
        $map = [];
        foreach (explode("\n", $content) as $line) {
            if (str_starts_with($line, 'use ') && str_ends_with($line, ';') && !str_contains($line, ',')) {
                $fqcn = substr($line, 4, -1);
                $map[$fqcn] = ($map[$fqcn] ?? 0) + 1;
            }
        }
        return $map;
    }
}
