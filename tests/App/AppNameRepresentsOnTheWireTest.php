<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppRegistry;
use MeRezaRezaei\Teleframe\App\AppResponder;
use MeRezaRezaei\Teleframe\App\AppTrigger;
use MeRezaRezaei\Teleframe\Handler\Update;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A DECLARED NAME MUST BE REPRESENTABLE ON THE WIRE (F16-4).
 *
 * A scheduled call names itself on the wire as ONE space-delimited workflow type
 * — AppCallSpec assembles it, WorkflowTypeCatalog::parseAppCall() parses it back
 * into the (app, call) pair — and it takes exactly two tokens. A name carrying
 * the separator therefore broke the round trip in two ways, and reported NEITHER:
 *
 *   1. the FALSE RED: the publisher armed the schedule and the worker registered
 *      the very same string (so the call ran, because the activity reads app/call
 *      off the payload), while the catalog could not parse it back, printed
 *      "NOT SERVED by any worker in this package … every tick of this schedule
 *      will fail to start a workflow" about a type this package HAD registered;
 *   2. the AMBIGUITY that makes it unfixable downstream: ('my app', 'status-ping')
 *      and ('my', 'app status-ping') render the IDENTICAL type string, so the pair
 *      is not recoverable from the string — no parser, from the ends or otherwise,
 *      can undo it.
 *
 * So the refusal belongs here, at declare time, on the row that caused it.
 *
 * This file asserts the guard is ON (the spaced names are refused, loudly, with a
 * usable message), that it is exactly as wide as the defect (a spaced APP name
 * with no scheduled call still works, because no type string is ever built for it),
 * and that it cannot be bypassed (define() is the only construction path).
 * The other half — worker and catalog AGREE for every accepted name — is asserted
 * in AppCallSpecTest, next to the payload composer that builds the type.
 */
final class AppNameRepresentsOnTheWireTest extends TestCase
{
    private static function handler(): \Closure
    {
        return static function (Update $u): void {};
    }

    public function test_an_app_name_carrying_the_wire_separator_is_refused_at_declare_time(): void
    {
        // RED on the pre-fix code: this define() was ACCEPTED, and the schedule it
        // armed reported NOT SERVED by a worker that had registered the type.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('may not contain a space');

        AppDefinition::define('my app', AppMode::Bot, calls: [
            AppCall::every('status-ping', 3600, 'messages.sendMessage', ['message' => 'alive']),
        ]);
    }

    public function test_a_call_name_carrying_the_wire_separator_is_refused_at_declare_time(): void
    {
        // The call name is the SECOND token of the same wire type, so it breaks it
        // exactly the same way — and it is refused even for a one-call app, because
        // a call exists only to be named on the wire.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('may not contain a space');

        AppDefinition::define('echo-bot', AppMode::Bot, calls: [
            AppCall::every('status ping', 3600, 'messages.sendMessage', ['message' => 'alive']),
        ]);
    }

    /** The three edge-space names, one named case each. */
    public static function edgeSpaceNames(): array
    {
        return [
            'leading space' => [' myapp'],
            'trailing space' => ['myapp '],
            'both edges' => [' myapp '],
        ];
    }

    /**
     * Both edge spaces produce an UNPARSEABLE type (two separators in a row),
     * so they are refused too — and NOT silently normalised: every consumer of a
     * name prints it verbatim (payload `app`/`call` keys, the `app:{name}` tag,
     * `teleframe:app list`), and rewriting the host's declared row behind its back
     * would also collapse two distinct rows onto one AppRegistry key.
     *
     * One case per name, so a failure says WHICH edge was trimmed instead of
     * asserting "something threw" once per loop turn.
     */
    #[DataProvider('edgeSpaceNames')]
    public function test_an_edge_space_in_a_name_is_refused_rather_than_trimmed_away(string $spaced): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('may not contain a space');

        AppDefinition::define($spaced, AppMode::Bot, calls: [
            AppCall::every('status-ping', 60, 'messages.sendMessage'),
        ]);
    }

    public function test_the_refusal_names_the_offending_row_and_a_legal_replacement(): void
    {
        // A refusal an operator cannot act on is not a fix. The message must name the
        // app, WHICH of its two names was bad, and a name that would be accepted.
        try {
            AppDefinition::define('my app', AppMode::Bot, calls: [
                AppCall::every('status ping', 3600, 'messages.sendMessage'),
            ]);
            self::fail('a spaced name on both halves must be refused');
        } catch (\InvalidArgumentException $e) {
            $message = $e->getMessage();
        }

        self::assertStringContainsString('app [my app]', $message, 'the refusal names the app');
        self::assertStringContainsString('call name [status ping]', $message,
            'the CALL name is refused first: it is the half that is actually on the wire');
        self::assertStringContainsString('status-ping', $message, 'the message offers a name that would be accepted');

        // And the offered replacement is genuinely accepted — a suggestion the guard
        // would itself reject is worse than no suggestion.
        $replaced = AppDefinition::define('my-app', AppMode::Bot, calls: [
            AppCall::every('status-ping', 3600, 'messages.sendMessage'),
        ]);
        self::assertSame('my-app', $replaced->name);
    }

    public function test_an_app_that_declares_no_call_keeps_a_spaced_name(): void
    {
        // THE GUARD IS EXACTLY AS WIDE AS THE DEFECT. With no scheduled call, no type
        // string is ever built for this app: the name is a registry key, a tag prefix
        // (`app:{name}`) and a CLI row label, and a space is harmless in all three.
        // Refusing it would take down a declaration that works, for a defect it cannot
        // reach — and would turn one bad row into a boot-time fatal (the F16-5 blast
        // radius) on every process, `php artisan` included.
        $app = AppDefinition::define('my app', AppMode::Bot, [
            AppTrigger::on('updateNewMessage', self::handler()),
        ]);

        self::assertSame('my app', $app->name, 'a call-less app keeps the name the host wrote');
        self::assertSame([], $app->calls(), 'nothing to arm');

        // The registry key and the responder tag both carry it verbatim — the round trip
        // the spaced name has to survive is the one that exists today.
        self::assertTrue((new AppRegistry)->define($app)->has('my app'));
        self::assertSame(1, (new AppResponder)->install($app), 'its triggers install as they always did');
    }

    public function test_define_is_the_only_way_to_build_an_app_definition(): void
    {
        // The refusal above is COMPLETE only because the constructor is private: there
        // is no second path that can put an unvalidated name in front of AppCallSpec.
        // If this ever fails, the guard has a hole and every other test here is a claim.
        $constructor = (new \ReflectionClass(AppDefinition::class))->getConstructor();

        self::assertNotNull($constructor, 'AppDefinition must still have a constructor');
        self::assertTrue($constructor->isPrivate(), 'the constructor must stay private, or define() is not the only door');
    }

    public function test_a_blank_name_is_still_refused_with_its_own_message(): void
    {
        // The pre-existing refusal is untouched by this one: a different rule, a
        // different message, and the reason (there is no name at all).
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('app name must be a non-empty string');

        AppDefinition::define('   ', AppMode::Bot, calls: [
            AppCall::every('status-ping', 60, 'messages.sendMessage'),
        ]);
    }
}
