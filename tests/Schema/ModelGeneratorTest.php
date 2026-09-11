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

    public function test_domain_model_for_user_type(): void
    {
        $files = self::generate();
        self::assertArrayHasKey('TlUser.php', $files);
        self::assertStringContainsString('final class TlUser extends TlAnchorModel', $files['TlUser.php']);
        self::assertStringContainsString("protected \$table = 'tf_users';", $files['TlUser.php']);
        self::assertStringContainsString('use AccountScoped;', $files['TlUser.php']);
    }

    public function test_domain_model_for_message_type(): void
    {
        $files = self::generate();
        self::assertArrayHasKey('TlMessage.php', $files);
        self::assertStringContainsString('final class TlMessage extends TlAnchorModel', $files['TlMessage.php']);
        self::assertStringContainsString("protected \$table = 'tf_messages';", $files['TlMessage.php']);
    }

    public function test_message_model_emits_peer_resolution_trait(): void
    {
        $files = self::generate();
        self::assertStringContainsString('PeerResolution', $files['TlMessage.php']);
        self::assertStringContainsString('use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;', $files['TlMessage.php']);
    }

    public function test_no_per_constructor_models(): void
    {
        $files = self::generate();
        // Domain model generator does not produce per-constructor models
        self::assertArrayNotHasKey('TlUserUserEmpty.php', $files);
        self::assertArrayNotHasKey('TlMessageMessage.php', $files);
        self::assertArrayNotHasKey('TlMessagesMessagesMessages.php', $files);
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

    // --- Domain model features ---

    public function test_domain_model_has_guarded_empty(): void
    {
        $files = self::generateFromString(
            "userEmpty#cafe0001 id:long = User;\n",
        );
        self::assertArrayHasKey('TlUser.php', $files);
        self::assertStringContainsString('protected $guarded = [];', $files['TlUser.php']);
    }

    public function test_domain_model_for_single_type_only(): void
    {
        $files = self::generateFromString(
            "userEmpty#cafe0001 id:long = User;\n",
        );
        // Only domain models, no per-constructor
        self::assertArrayHasKey('TlUser.php', $files);
        self::assertArrayNotHasKey('TlUserUserEmpty.php', $files);
    }

    public function test_account_scoped_trait_on_domain_models(): void
    {
        $models = self::generateFromString(
            "userEmpty#cafe0001 id:long = User;\n",
        );
        $anchor = $models['TlUser.php'];
        self::assertStringContainsString('use MeRezaRezaei\\Teleframe\\Schema\\Eloquent\\AccountScoped;', $anchor);
        self::assertStringContainsString('use AccountScoped;', $anchor);
    }

    public function test_peer_ref_model_emits_peer_resolution_trait(): void
    {
        $models = self::generateFromString(
            "message#dead0001 id:int peer_id:Peer = Message;\n"
            . "userPeer#dead0002 user_id:long = Peer;\n"
            . "chatPeer#dead0003 chat_id:long = Peer;\n",
        );
        self::assertArrayHasKey('TlMessage.php', $models);
        self::assertStringContainsString('use MeRezaRezaei\\Teleframe\\Schema\\Eloquent\\PeerResolution;', $models['TlMessage.php']);
    }

    public function test_non_peer_model_does_not_get_peer_resolution(): void
    {
        $models = self::generateFromString(
            "userEmpty#cafe0001 id:long = User;\n",
        );
        self::assertStringNotContainsString('PeerResolution', $models['TlUser.php']);
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
}
