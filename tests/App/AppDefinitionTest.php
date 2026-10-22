<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppTrigger;
use MeRezaRezaei\Teleframe\Handler\Update;
use PHPUnit\Framework\TestCase;

/**
 * AppDefinition (design §2/G1) — the immutable declarative program. define()
 * is loud: a blank name refuses, and trigger/call lists whose elements are
 * not AppTrigger/AppCall instances throw InvalidArgumentException at declare
 * time (never at dispatch). Storage is typed (triggers()/calls() return only
 * the validated rows), triggerFor() is the exact-match-or-null first-match
 * posture, and the row is immutable (readonly fields, copied validated
 * lists — later mutation of an input array cannot leak into the row).
 */
final class AppDefinitionTest extends TestCase
{
    private static function handler(): \Closure
    {
        return static function (Update $u): void {};
    }

    public function test_define_rejects_a_blank_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('app name must be a non-empty string');

        AppDefinition::define('   ', AppMode::Bot);
    }

    public function test_define_rejects_non_app_trigger_elements(): void
    {
        try {
            AppDefinition::define('echo-bot', AppMode::Bot, ['not-a-trigger']);
            self::fail('a non-AppTrigger element must refuse at declare time');
        } catch (InvalidArgumentException $e) {
            self::assertStringContainsString('echo-bot', $e->getMessage());
            self::assertStringContainsString('AppTrigger', $e->getMessage());
            self::assertStringContainsString('index 0', $e->getMessage());
        }

        // A mixed list refuses too — the offending index is named.
        try {
            AppDefinition::define('echo-bot', AppMode::Bot, [
                AppTrigger::on('updateNewMessage', self::handler()),
                ['match' => '*', 'handler' => self::handler()],
            ]);
            self::fail('a mixed trigger list with a bad element must refuse');
        } catch (InvalidArgumentException $e) {
            self::assertStringContainsString('index 1', $e->getMessage());
        }
    }

    public function test_define_rejects_non_app_call_elements(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('call list may only contain AppCall instances');

        AppDefinition::define('echo-bot', AppMode::User, calls: ['status-ping']);
    }

    public function test_define_stores_typed_rows_and_exposes_them(): void
    {
        $trigger = AppTrigger::on('updateNewMessage', self::handler());
        $call = AppCall::every('status-ping', 3600, 'messages.sendMessage', ['message' => 'alive']);

        $app = AppDefinition::define('echo-bot', AppMode::Bot, [$trigger], [$call]);

        self::assertSame('echo-bot', $app->name);
        self::assertSame(AppMode::Bot, $app->mode);
        self::assertSame([$trigger], $app->triggers());
        self::assertSame([$call], $app->calls());
        self::assertSame([$trigger], $app->triggers);
        self::assertSame([$call], $app->calls);
    }

    public function test_to_array_round_trips_the_declarative_row(): void
    {
        $app = AppDefinition::define('echo-bot', AppMode::User, [
            AppTrigger::on('updateNewMessage', self::handler(), priority: 3, onOwn: true),
        ], [
            AppCall::every('status-ping', 60, 'sendMessage', ['chat_id' => 7, 'text' => 'alive']),
        ]);

        $row = $app->toArray();

        self::assertSame('echo-bot', $row['name']);
        self::assertSame('user', $row['mode']);
        self::assertSame([
            ['match' => 'updateNewMessage', 'handler' => self::class.'_handler', 'priority' => 3, 'on_own' => true],
        ], self::normalizeHandler($row['triggers']));
        self::assertSame([
            ['name' => 'status-ping', 'interval_seconds' => 60, 'method' => 'sendMessage', 'params' => ['chat_id' => 7, 'text' => 'alive']],
        ], $row['calls']);
    }

    public function test_trigger_for_returns_the_exact_match_else_null(): void
    {
        $exact = AppTrigger::on('updateNewMessage', self::handler());
        $other = AppTrigger::on('updateNewChannel', self::handler());

        $app = AppDefinition::define('echo-bot', AppMode::Bot, [$exact, $other]);

        self::assertSame($exact, $app->triggerFor('updateNewMessage'));
        self::assertSame($other, $app->triggerFor('updateNewChannel'));
        self::assertNull(
            $app->triggerFor('updateNewChannelMember'),
            'a name nobody registered answers null',
        );
        // exact only — the matcher's prefix grammar is NOT triggerFor's:
        // a '*' pattern id never answers an exact query
        self::assertNull(
            $app->triggerFor('updateNewMessage*'),
            "triggerFor() is EXACT: the '*' suffix grammar belongs to the matcher, so a wildcard id "
            .'never answers an exact query even though the matcher would fire it',
        );
        self::assertNull(
            $app->triggerFor('nothing-here'),
            'and a name no trigger declares at all is null too',
        );
    }

    public function test_definition_is_immutable_against_input_mutation(): void
    {
        $triggers = [AppTrigger::on('updateNewMessage', self::handler())];
        $calls = [AppCall::every('status-ping', 3600, 'messages.sendMessage')];

        $app = AppDefinition::define('echo-bot', AppMode::Bot, $triggers, $calls);

        $triggers[] = AppTrigger::on('*', self::handler());
        $calls[] = AppCall::every('second', 60, 'messages.sendMessage');

        self::assertCount(
            1,
            $app->triggers(),
            'appending to the caller\'s trigger array after define() must not reach the definition — '
            .'the trigger this test appended is for the caller, not for echo-bot',
        );
        self::assertCount(
            1,
            $app->calls(),
            'same for a call appended after define(): one call stays one call',
        );
        self::assertSame('echo-bot', $app->name, 'and the name the definition carries is the declared one');
    }

    public function test_a_trigger_with_two_sscanf_tokens_refuses_at_declare_time(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('more than one %s token');

        AppTrigger::on('/start %s %s', self::handler());
    }

    /**
     * The toArray handler cell carries the raw handler form; for the
     * closure row we assert shape via a placeholder so the assertion is not
     * byte-coupled to a closure instance.
     *
     * @param  list<array<string, mixed>>  $triggerRows
     * @return list<array<string, mixed>>
     */
    private static function normalizeHandler(array $triggerRows): array
    {
        foreach ($triggerRows as $i => $row) {
            $triggerRows[$i]['handler'] = self::class.'_handler';
        }

        return $triggerRows;
    }
}
