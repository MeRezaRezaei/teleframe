<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use PHPUnit\Framework\TestCase;
use MeRezaRezaei\Teleframe\Schema\Generator\SchemaRegenerator;
use MeRezaRezaei\Teleframe\Schema\Generator\TeleframeSchemeLoader;
use MeRezaRezaei\Teleframe\Schema\Generator\TlCanon;

/**
 * Task 2 gate: the metamodel loads from this package's committed copy of
 * the owner's full v227 mirror (schema/sources/TL_telegram_v227.tl +
 * TL_mtproto_v1.tl + TL_secret.tl) via teleframe's TLSignatureParser —
 * with teleproto's partial vendored sources as documented fallback.
 *
 * Count reality (owner's real fork run, verified locally): the v227
 * mirror carries 1685 constructors / 799 methods at LAYER 227 — the
 * same metamodel the fork's committed manifest records. Bands bracket
 * those with layer-drift headroom.
 */
final class MetamodelFromTeleframeTest extends TestCase
{
    private static TlSchemeCache $cache;

    public static function setUpBeforeClass(): void
    {
        self::$cache = new TlSchemeCache();
    }

    private function scheme(): \MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme
    {
        return self::$cache->scheme ??= (new SchemaRegenerator())->loadScheme();
    }

    public function test_loads_layer_227_from_committed_mirror(): void
    {
        self::assertSame(227, $this->scheme()->layer);
    }

    public function test_method_and_constructor_bands(): void
    {
        $counts = $this->scheme()->counts();
        self::assertGreaterThan(1500, $counts['constructors'], 'constructors lower bound');
        self::assertLessThan(2500, $counts['constructors'], 'constructors upper bound');
        self::assertGreaterThan(750, $counts['methods'], 'methods lower bound');
        self::assertLessThan(950, $counts['methods'], 'methods upper bound');
    }

    public function test_user_constructor_spot_check(): void
    {
        $user = $this->scheme()->types()['User']->constructors()['user'] ?? null;
        self::assertNotNull($user, 'constructor user missing');
        // Ground truth: the owner's v227 mirror carries user#31774388
        // (hex as written in the .tl source) — the wire id our Layer-227
        // MTProto speaks.
        self::assertSame(829899656, $user->id);
        self::assertSame('31774388', sprintf('%08x', $user->id));
        $id = $user->params()['id'] ?? null;
        self::assertNotNull($id, 'user.id param missing');
        self::assertSame('long', $id->baseType());
    }

    public function test_messages_send_message_spot_check(): void
    {
        $m = $this->scheme()->methods()['messages.sendMessage'] ?? null;
        self::assertNotNull($m, 'method messages.sendMessage missing');
        $message = $m->params()['message'] ?? null;
        self::assertNotNull($message, 'messages.sendMessage.message param missing');
        self::assertSame('string', $message->baseType());
        $silent = $m->params()['silent'] ?? null;
        self::assertNotNull($silent);
        self::assertSame([['flags', 5]], [$silent->conditional()]);
    }

    public function test_mtproto_sources_merged(): void
    {
        self::assertArrayHasKey('pong', $this->scheme()->types()['Pong']->constructors());
        self::assertArrayHasKey('ping', $this->scheme()->methods());
    }

    public function test_secret_schema_merged_across_layer_fences(): void
    {
        // TL_secret.tl is split by MadelineProto "===N===" layer fences;
        // ctors on both sides of every fence must load.
        self::assertArrayHasKey('decryptedMessage', $this->scheme()->types()['DecryptedMessage']->constructors());
        self::assertArrayHasKey(
            'decryptedMessageActionReadMessages',
            $this->scheme()->types()['DecryptedMessageAction']->constructors(),
        );
    }

    public function test_id_less_constructors_get_tlcanon_crc(): void
    {
        $ctor = $this->scheme()->types()['RSAPublicKey']->constructors()['rsa_public_key'] ?? null;
        self::assertNotNull($ctor, 'id-less rsa_public_key missing');
        $expected = hexdec(hash('crc32b', TlCanon::normalize('rsa_public_key n:string e:string = RSAPublicKey')));
        self::assertSame($expected, $ctor->id, 'id must be TlCanon-computed, never invented');
        self::assertNotSame(0, $ctor->id);
    }

    public function test_generic_wrappers_survive_as_fillers(): void
    {
        $m = $this->scheme()->methods()['invokeWithLayer'] ?? null;
        self::assertNotNull($m);
        self::assertSame('X', $m->returnType);
        self::assertSame('da9b0d0d', sprintf('%08x', $m->id));
        $named = $m->params();
        self::assertArrayNotHasKey('__generic__', $named, 'generic fillers must be excluded');
        self::assertSame('int', $named['layer']->baseType());
    }

    public function test_default_sources_dir_is_our_mirror(): void
    {
        $dir = TeleframeSchemeLoader::defaultSourcesDir();
        self::assertFileExists($dir . '/TL_telegram_v227.tl');
        self::assertFileExists($dir . '/TL_mtproto_v1.tl');
        self::assertFileExists($dir . '/TL_secret.tl');

        $scheme = (new SchemaRegenerator())->loadScheme($dir);
        self::assertSame(227, $scheme->layer);
        self::assertGreaterThan(750, $scheme->counts()['methods']);
        self::assertGreaterThan(1500, $scheme->counts()['constructors']);
    }
}

/** Tiny static cache so the multi-thousand-line parse happens once per test run. */
final class TlSchemeCache
{
    public ?\MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme $scheme = null;
}
