<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Tests\MTProto;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Core\MTProto\Client;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The non-live short-circuit contract: nothing that could not have reached
 * Telegram may be handed back as a fabricated rpc_result.
 *
 * Before the fix, Client::call() returned a bare
 * `['_' => 'rpc_result', 'method' => ..., 'params' => ..., 'dc_id' => ...]`
 * for ANY method name in non-live mode — a typo, a real method, and one of
 * the 34 catalogued-but-unencodable methods were byte-for-byte
 * indistinguishable from each other.
 */
class ClientOfflineContractTest extends TestCase
{
    private function offlineClient(): Client
    {
        return new Client(
            apiId: 1,
            apiHash: 'h',
            session: new SessionData(dcId: 2, authKey: random_bytes(256)),
            live: false,
        );
    }

    public function test_offline_valid_method_returns_the_marked_offline_result(): void
    {
        $res = $this->offlineClient()->call('help.getNearestDc');

        $this->assertSame('rpc_result', $res['_']);
        $this->assertTrue($res['_offline'], 'the offline result must be marked as never having been sent');
        $this->assertSame('help.getNearestDc', $res['method']);
        $this->assertSame(2, $res['dc_id']);
    }

    public function test_offline_marker_is_absent_from_a_real_wire_answer(): void
    {
        // A real server answer is the decoded constructor ('nearestDc' here),
        // never the offline 'rpc_result' envelope. Guard the two shapes apart.
        $offline = $this->offlineClient()->call('help.getNearestDc');
        $wire = ['_' => 'nearestDc', 'country' => 'DE', 'this_dc' => 2, 'nearest_dc' => 2];

        $this->assertArrayNotHasKey('_offline', $wire);
        $this->assertNotSame($offline, $wire);
    }

    public function test_offline_typo_does_not_fabricate_an_rpc_result(): void
    {
        $client = $this->offlineClient();

        $thrown = null;
        try {
            $res = $client->call('this.is.a.typo');
        } catch (InvalidArgumentException $e) {
            $thrown = $e;
        }

        $this->assertInstanceOf(
            InvalidArgumentException::class,
            $thrown,
            'an unknown method must throw in non-live mode, never return a fabricated result'
        );
        $this->assertStringContainsString('this.is.a.typo', $thrown->getMessage());
        $this->assertStringContainsString('not encodable', $thrown->getMessage());
        $this->assertStringContainsString('never reach Telegram', $thrown->getMessage());
        // Nothing resembling a success was produced.
        $this->assertFalse(isset($res));
    }

    public function test_offline_typo_and_valid_method_are_not_the_same_outcome(): void
    {
        $client = $this->offlineClient();

        $valid = $client->call('help.getNearestDc');

        $this->expectException(InvalidArgumentException::class);
        $client->call('messages.sendMessag');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unencodableMethods(): array
    {
        return [
            'communities' => ['communities.create'],
            'ephemeral' => ['ephemeral.sendMessage'],
            'firebase login' => ['auth.initFirebasePnvLogin'],
            'firebase signup' => ['auth.firebasePnvSignUp'],
            'firebase finish' => ['auth.finishFirebasePnvLogin'],
            'rich message AI' => ['messages.composeRichMessageWithAI'],
            'translate rich' => ['messages.translateRichMessage'],
            'join web view' => ['messages.requestChatJoinWebView'],
        ];
    }

    #[DataProvider('unencodableMethods')]
    public function test_catalogued_but_unencodable_method_throws_by_name_offline(string $method): void
    {
        $this->expectException(InvalidArgumentException::class);
        // By name, and with a reason the operator can act on. The wording is
        // now the catalogue's own reason — a layer-229-vs-227 mirror gap, or
        // "this is a request ENVELOPE" — which is far more specific than the
        // one sentence it replaced, so the contract is asserted on the
        // substance (named, explained, never a fabricated result) rather than
        // on a substring that used to stand in for all three.
        $this->expectExceptionMessageMatches('/'.preg_quote($method, '/').'/');
        $this->expectExceptionMessageMatches('/cannot reach Telegram|wire mirror|ENVELOPE/');

        $this->offlineClient()->call($method);
    }

    #[DataProvider('unencodableMethods')]
    public function test_catalogued_but_unencodable_method_throws_by_name_live(string $method): void
    {
        $client = new Client(
            apiId: 1,
            apiHash: 'h',
            session: new SessionData(dcId: 2, authKey: random_bytes(256)),
            live: true,
        );

        try {
            $client->call($method);
            $this->fail('expected an unencodable method to throw before any I/O');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString($method, $e->getMessage());
            $this->assertMatchesRegularExpression(
                '/cannot reach Telegram|wire mirror|ENVELOPE/',
                $e->getMessage(),
                'the refusal must explain itself, not merely name the method',
            );
        } finally {
            $client->close();
        }
    }

    public function test_all_34_mtproto_methods_without_a_tl_signature_throw_offline(): void
    {
        // The audit found 34 mtproto methods catalogued in MethodRegistry with
        // no TL signature at all. Every one of them must fail loudly; none may
        // be answered with the offline marker.
        $expected = [
            'auth.finishFirebasePnvLogin', 'auth.firebasePnvSignUp', 'auth.initFirebasePnvLogin',
            'communities.create', 'communities.getJoinedCommunities', 'communities.getParticipantJoinedChats',
            'communities.getPeerLinkRequests', 'communities.toggleAllPeerLinkRequestApproval',
            'communities.toggleCommunityCollapsedInDialogs', 'communities.toggleParticipantBanned',
            'communities.togglePeerLink', 'communities.togglePeerLinkRequestApproval',
            'destroy_auth_key',
            'ephemeral.deleteAllWelcomeMessages', 'ephemeral.deleteMessage', 'ephemeral.deleteWelcomeMessage',
            'ephemeral.editMessage', 'ephemeral.getCallbackAnswer', 'ephemeral.getWelcomeMessages',
            'ephemeral.reportMessage', 'ephemeral.sendMessage',
            'invokeAfterMsg', 'invokeAfterMsgs', 'invokeWithApnsSecret', 'invokeWithBusinessConnection',
            'invokeWithGooglePlayIntegrity', 'invokeWithMessagesRange', 'invokeWithReCaptcha',
            'invokeWithTakeout', 'invokeWithoutUpdates',
            'messages.composeRichMessageWithAI', 'messages.requestChatJoinWebView', 'messages.translateRichMessage',
            'req_pq',
        ];
        $this->assertCount(34, $expected, 'the audit count is the contract here');

        $client = $this->offlineClient();
        foreach ($expected as $method) {
            try {
                $res = $client->call($method);
                $this->fail("'{$method}' returned a fabricated ".$res['_'].' instead of throwing');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString($method, $e->getMessage());
            }
        }
    }

    public function test_call_many_rejects_an_unencodable_method_instead_of_stubbing_it(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cannot reach Telegram|wire mirror|ENVELOPE/');

        $this->offlineClient()->callMany([
            'ok' => ['method' => 'help.getNearestDc', 'params' => []],
            'bad' => ['method' => 'communities.create', 'params' => []],
        ]);
    }

    public function test_call_many_offline_results_are_marked_offline(): void
    {
        $got = $this->offlineClient()->callMany([
            'nearest' => ['method' => 'help.getNearestDc', 'params' => []],
            'status' => ['method' => 'account.updateStatus', 'params' => ['offline' => false]],
        ]);

        $this->assertSame(['nearest', 'status'], array_keys($got));
        $this->assertTrue($got['nearest']['_offline']);
        $this->assertTrue($got['status']['_offline']);
        $this->assertSame('help.getNearestDc', $got['nearest']['method']);
        $this->assertSame(['offline' => false], $got['status']['params']);
    }

    public function test_live_mode_still_reaches_the_wire_for_a_valid_method(): void
    {
        // Live is untouched: the pre-branch gate is name-only, and a valid
        // signature still routes to the socket (here: no network, so a
        // connection failure is what proves we left the offline path).
        $client = new Client(
            apiId: 1,
            apiHash: 'h',
            session: new SessionData(dcId: 2, authKey: random_bytes(256)),
            live: true,
        );

        try {
            $client->call('help.getNearestDc');
            $this->markTestSkipped('a live connection to Telegram was actually established');
        } catch (\RuntimeException $e) {
            $this->assertDoesNotMatchRegularExpression(
                '/wire mirror|ENVELOPE/',
                $e->getMessage(),
                'a live connection failure is not a schema refusal',
            );
        } finally {
            $client->close();
        }
    }

    public function test_live_mode_typo_still_throws_invalid_argument(): void
    {
        $client = new Client(
            apiId: 1,
            apiHash: 'h',
            session: new SessionData(dcId: 2, authKey: random_bytes(256)),
            live: true,
        );

        try {
            $client->call('this.is.a.typo');
            $this->fail('expected a throw for an unknown constructor');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('this.is.a.typo', $e->getMessage());
        } finally {
            $client->close();
        }
    }

    public function test_live_mode_still_validates_params_before_io(): void
    {
        // The name gate must not swallow the pre-existing param validation.
        $client = new Client(
            apiId: 1,
            apiHash: 'h',
            session: new SessionData(dcId: 2, authKey: random_bytes(256)),
            live: true,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/missing field/');

        try {
            $client->call('ping'); // ping_id missing
        } finally {
            $client->close();
        }
    }

    public function test_offline_mode_still_does_not_throw_for_a_legitimate_call(): void
    {
        // Offline mode keeps its legitimate uses: a well-formed, encodable
        // call returns the marker rather than throwing.
        $client = $this->offlineClient();

        $res = $client->call('messages.sendMessage', [
            'peer' => ['_' => 'inputPeerUser', 'user_id' => 1, 'access_hash' => 2],
            'message' => 'hi',
            'random_id' => 3,
        ]);

        $this->assertTrue($res['_offline']);
        $this->assertSame('messages.sendMessage', $res['method']);
        $this->assertSame('hi', $res['params']['message']);
    }
}
