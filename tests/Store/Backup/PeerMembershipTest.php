<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Backup;

use MeRezaRezaei\Teleframe\Store\Backup\BackupException;
use MeRezaRezaei\Teleframe\Store\Backup\PeerMembership;
use MeRezaRezaei\Teleframe\Store\Backup\PeerTarget;
use MeRezaRezaei\Teleframe\Store\ResilientCall;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * PeerMembership (2026-09-24 backup-store remainder design §2/§6, cycle 11
 * wave 2) — the add-member seam for channel/supergroup backup targets,
 * offline through an anonymous-class scope stub (zero container, zero
 * wire). The scope is a plain `object` — the RelayTerminal bound-client
 * idiom — so a recording stub double is the whole double surface.
 *
 * Coverage: ensureMember funnels the inputPeer + accounts into the scope's
 * inviteToChannel (and through the store's ResilientCall posture when one
 * is injected: method 'channels.inviteToChannel', params {channel, users});
 * transport failure rethrows (logging never swallows); joinPublic passes
 * the peer positionally to joinChannel; hasPostRights is TRUE for channel
 * targets without any scope call, and probes getParticipants (admins
 * filter) for groups — best-effort, never fatal; missing scope methods
 * throw BackupException LOUD; a NULL scope throws BackupException at
 * FIRST USE, never at construction (reconcile ruling); and NOTHING
 * session-string-shaped ever reaches the funnel (accounts are ids /
 * usernames only).
 */
final class PeerMembershipTest extends TestCase
{
    private const PEER = ['_' => 'inputPeerChannel', 'channel_id' => 123, 'access_hash' => 456];

    private function target(): PeerTarget
    {
        return new PeerTarget('nightly', 123, 456);
    }

    private function groupTarget(): PeerTarget
    {
        return new PeerTarget('nightly', 123, 456, peerType: 'group', mode: 'raw');
    }

    public function test_ensure_member_funnels_peer_and_accounts_into_the_scope(): void
    {
        $scope = new class
        {
            /** @var list<array{0: mixed, 1: array}> */
            public array $invited = [];

            /** @param  list<int|string>  $users */
            public function inviteToChannel(mixed $channel, array $users): array
            {
                $this->invited[] = [$channel, $users];

                return ['_' => 'updates', 'seq' => 1];
            }
        };

        $result = (new PeerMembership($scope))->ensureMember($this->target(), [1, 'alice']);

        self::assertSame([['_' => 'updates', 'seq' => 1]], [$result]);
        self::assertSame([self::PEER], [$scope->invited[0][0]]);
        self::assertSame([1, 'alice'], $scope->invited[0][1]);
    }

    public function test_ensure_member_funnels_through_the_resilient_call_posture(): void
    {
        $scope = new class
        {
            public function inviteToChannel(mixed $channel, array $users): array
            {
                throw new RuntimeException('scope must not be reached when a ResilientCall is injected');
            }
        };

        $recorded = null;
        $call = new ResilientCall(static function (string $method, array $params) use (&$recorded): array {
            $recorded = [$method, $params];

            return ['_' => 'updates'];
        });

        $result = (new PeerMembership($scope, $call))->ensureMember($this->target(), [7]);

        self::assertSame('channels.inviteToChannel', $recorded[0]);
        self::assertSame(['channel' => self::PEER, 'users' => [7]], $recorded[1]);
        self::assertSame(['_' => 'updates'], $result);
    }

    public function test_transport_failure_rethrows_never_swallowed(): void
    {
        $scope = new class
        {
            public function inviteToChannel(mixed $channel, array $users): array
            {
                throw new RuntimeException('FLOOD_WAIT_60 mirror — transport owns retry');
            }
        };

        try {
            (new PeerMembership($scope))->ensureMember($this->target(), [1]);
            self::fail('expected the transport exception to rethrow');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('FLOOD_WAIT_60 mirror', $e->getMessage());
        }
    }

    public function test_join_public_passes_the_peer_positionally(): void
    {
        $scope = new class
        {
            /** @var list<mixed> */
            public array $joined = [];

            public function joinChannel(mixed $channel): array
            {
                $this->joined[] = $channel;

                return ['_' => 'updates'];
            }
        };

        $result = (new PeerMembership($scope))->joinPublic($this->target());

        self::assertSame(['_' => 'updates'], $result);
        self::assertSame([self::PEER], $scope->joined);
    }

    public function test_has_post_rights_channel_target_is_true_without_any_scope_call(): void
    {
        $scope = new class
        {
            public array $probed = [];

            public function getParticipants(mixed $channel, array $filter, int $offset, int $limit): array
            {
                $this->probed[] = [$channel, $filter, $offset, $limit];

                return ['_' => 'channelParticipants'];
            }
        };

        $membership = new PeerMembership($scope);

        self::assertTrue($membership->hasPostRights($this->target()));
        self::assertSame([], $scope->probed, 'channel targets are assumed owned — no probe');
    }

    public function test_has_post_rights_group_probes_get_participants_with_the_admins_filter(): void
    {
        $scope = new class
        {
            /** @var list<array{0: mixed, 1: array, 2: int, 3: int}> */
            public array $probed = [];

            public function getParticipants(mixed $channel, array $filter, int $offset, int $limit): array
            {
                $this->probed[] = [$channel, $filter, $offset, $limit];

                return ['_' => 'channelParticipants'];
            }
        };

        $membership = new PeerMembership($scope);

        self::assertTrue($membership->hasPostRights($this->groupTarget()));
        self::assertSame([[
            self::PEER,
            ['_' => 'channelParticipantsAdmins'],
            0,
            50,
        ]], $scope->probed);
    }

    public function test_has_post_rights_probe_failure_is_permissive_true_and_never_fatals(): void
    {
        $scope = new class
        {
            public function getParticipants(mixed $channel, array $filter, int $offset, int $limit): array
            {
                throw new RuntimeException('transport down — probe is best-effort');
            }
        };

        self::assertTrue((new PeerMembership($scope))->hasPostRights($this->groupTarget()));
    }

    public function test_has_post_rights_with_null_scope_skips_the_probe_permissively(): void
    {
        // The NEVER-throw contract survives a null scope (reconcile
        // ruling): probe skipped, permissive true.
        self::assertTrue((new PeerMembership)->hasPostRights($this->groupTarget()));
        self::assertTrue((new PeerMembership)->hasPostRights($this->target()));
    }

    // ── probePostRights: the STRICT three-state gate the upload path uses

    public function test_strict_probe_is_true_for_a_channel_target_without_probing(): void
    {
        $scope = new class
        {
            public array $probed = [];

            public function getParticipants(mixed $channel, array $filter, int $offset, int $limit): array
            {
                $this->probed[] = [$channel, $filter, $offset, $limit];

                return ['count' => 0, 'participants' => []];
            }
        };

        self::assertTrue((new PeerMembership($scope))->probePostRights($this->target()));
        self::assertSame([], $scope->probed, 'channel targets stay assumed-owned — no probe');
    }

    public function test_strict_probe_denies_a_group_with_an_empty_admin_set(): void
    {
        $scope = new class
        {
            public function getParticipants(mixed $channel, array $filter, int $offset, int $limit): array
            {
                return ['_' => 'channelParticipants', 'count' => 0, 'participants' => []];
            }
        };

        self::assertFalse((new PeerMembership($scope))->probePostRights($this->groupTarget()));
    }

    public function test_strict_probe_allows_a_group_with_admins(): void
    {
        $scope = new class
        {
            public function getParticipants(mixed $channel, array $filter, int $offset, int $limit): array
            {
                return ['_' => 'channelParticipants', 'count' => 3, 'participants' => [1, 2, 3]];
            }
        };

        self::assertTrue((new PeerMembership($scope))->probePostRights($this->groupTarget()));
    }

    public function test_strict_probe_is_unverifiable_not_a_denial_when_the_probe_fails(): void
    {
        $scope = new class
        {
            public function getParticipants(mixed $channel, array $filter, int $offset, int $limit): array
            {
                throw new RuntimeException('transport down');
            }
        };

        // null, NOT false: "could not tell" must not masquerade as "cannot
        // post", or an offline install could never upload at all.
        self::assertNull((new PeerMembership($scope))->probePostRights($this->groupTarget()));
        self::assertNull((new PeerMembership)->probePostRights($this->groupTarget()));
        self::assertNull((new PeerMembership(new class {}))->probePostRights($this->groupTarget()));
    }

    public function test_strict_probe_is_unverifiable_when_the_reply_says_nothing_about_participants(): void
    {
        $scope = new class
        {
            public function getParticipants(mixed $channel, array $filter, int $offset, int $limit): array
            {
                return ['_' => 'channelParticipants'];
            }
        };

        self::assertNull((new PeerMembership($scope))->probePostRights($this->groupTarget()));
    }

    public function test_the_permissive_probe_still_never_denies(): void
    {
        // The documented ruling is unchanged: hasPostRights() stays
        // permissive for the prune guard, and only the upload path uses the
        // strict probe. This pins the two are not the same method.
        $scope = new class
        {
            public function getParticipants(mixed $channel, array $filter, int $offset, int $limit): array
            {
                return ['count' => 0, 'participants' => []];
            }
        };

        $membership = new PeerMembership($scope);
        self::assertTrue($membership->hasPostRights($this->groupTarget()));
        self::assertFalse($membership->probePostRights($this->groupTarget()));
    }

    public function test_missing_scope_method_throws_backup_exception_loud(): void
    {
        $scope = new class
        {
            // No inviteToChannel / joinChannel — the seam must say so loud.
        };

        try {
            (new PeerMembership($scope))->ensureMember($this->target(), [1]);
            self::fail('expected BackupException for a scope without inviteToChannel');
        } catch (BackupException $e) {
            self::assertStringContainsString('inviteToChannel', $e->getMessage());
        }

        try {
            (new PeerMembership($scope))->joinPublic($this->target());
            self::fail('expected BackupException for a scope without joinChannel');
        } catch (BackupException $e) {
            self::assertStringContainsString('joinChannel', $e->getMessage());
        }
    }

    public function test_null_scope_throws_loud_at_first_use_never_at_construction(): void
    {
        $membership = new PeerMembership(null);
        // Construction is fine (plain-PHP); FIRST USE throws loud.
        self::assertInstanceOf(PeerMembership::class, $membership);

        try {
            $membership->ensureMember($this->target(), [1]);
            self::fail('expected BackupException at first use with a null scope');
        } catch (BackupException $e) {
            self::assertStringContainsString('no scope bound', $e->getMessage());
        }
    }

    public function test_nothing_session_string_shaped_reaches_the_funnel(): void
    {
        $scope = new class
        {
            /** @var list<array{0: mixed, 1: array}> */
            public array $invited = [];

            public function inviteToChannel(mixed $channel, array $users): array
            {
                $this->invited[] = [$channel, $users];

                return ['_' => 'updates'];
            }
        };

        $planted = '1:AAAAsession_string_that_must_never_leak';
        $membership = new PeerMembership($scope);
        $membership->ensureMember($this->target(), [5, 'alice']);

        // The funnel carries peer id/access hash + ids/usernames ONLY —
        // scan every touched value for the planted session string and for
        // the ':' base64 session prefix.
        $touched = [$scope->invited[0][0], $scope->invited[0][1]];
        $serialized = json_encode($touched, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString($planted, $serialized);
        self::assertStringNotContainsString('session', strtolower($serialized));
        self::assertStringNotContainsString('1:', $serialized);
    }
}
