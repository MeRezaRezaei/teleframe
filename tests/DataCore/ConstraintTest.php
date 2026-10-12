<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\DataCore;

use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\Constraint;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\DefaultValue;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\Enum;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\Indexed;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\Integer;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\Range;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\Relation;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\Required;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\StringValue;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\Unique;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pins the DataCore constraint hierarchy against plan §1
 * (docs/superpowers/plans/2026-09-09-data-core-metamodel-design.md): the
 * exact laravelRules() arrays, check() predicates and description() strings
 * are contract. A sibling's small API drift fails loudly here, not at the
 * wave gate.
 */
final class ConstraintTest extends TestCase
{
    // Required ------------------------------------------------------------

    public function test_required(): void
    {
        $c = new Required;

        self::assertSame('Required', $c->name());
        self::assertSame('a missing value is a flow shifter: the intake must refuse', $c->description());
        self::assertSame(['required'], $c->laravelRules());
        self::assertTrue($c->check('x'));
        self::assertTrue($c->check(0));
        self::assertTrue($c->check(false));   // plan §1: $value !== null && $value !== ''
        self::assertFalse($c->check(null));
        self::assertFalse($c->check(''));
    }

    // Integer -------------------------------------------------------------

    /** @return iterable<string, array{int, list<string>}> */
    public static function integerRulesProvider(): iterable
    {
        yield 'bare integer' => [new Integer, ['integer']];
        yield 'both bounds' => [new Integer(0, 255), ['integer', 'between:0,255']];
        yield 'narrow band' => [new Integer(1, 10), ['integer', 'between:1,10']];
        yield 'max only (min defaults to 0)' => [new Integer(null, 5), ['integer', 'between:0,5']];
        yield 'min only (max defaults to PHP_INT_MAX)' => [new Integer(7), ['integer', 'between:7,'.PHP_INT_MAX]];
    }

    #[DataProvider('integerRulesProvider')]
    public function test_integer_laravel_rules(Integer $c, array $expected): void
    {
        self::assertSame($expected, $c->laravelRules());
    }

    /** @return iterable<string, array{int, mixed, bool}> */
    public static function integerCheckProvider(): iterable
    {
        yield 'in band' => [new Integer(0, 255), 100, true];
        yield 'lower edge' => [new Integer(0, 255), 0, true];
        yield 'upper edge' => [new Integer(0, 255), 255, true];
        yield 'below min' => [new Integer(0, 255), -1, false];
        yield 'above max' => [new Integer(0, 255), 256, false];
        yield 'float rejected' => [new Integer(0, 255), 3.14, false];
        yield 'numeric string rejected' => [new Integer(0, 255), '50', false];
        yield 'null rejected' => [new Integer, null, false];
        yield 'min only lower edge' => [new Integer(5), 5, true];
        yield 'min only below' => [new Integer(5), 4, false];
        yield 'max only upper edge' => [new Integer(null, 5), 5, true];
        yield 'max only above' => [new Integer(null, 5), 6, false];
    }

    #[DataProvider('integerCheckProvider')]
    public function test_integer_check(Integer $c, mixed $value, bool $expected): void
    {
        self::assertSame('Integer', $c->name());
        self::assertSame('peer and telegram ids are 64-bit integers; math limits the meaning', $c->description());
        self::assertSame($expected, $c->check($value));
    }

    // Enum ----------------------------------------------------------------

    public function test_enum_string_values(): void
    {
        $c = new Enum(['act_on', 'store_only']);

        self::assertSame('Enum', $c->name());
        self::assertSame("the field may only be one of the declared words — the 'and' algebra of the verbatim", $c->description());
        self::assertSame(['in:act_on,store_only'], $c->laravelRules());
        self::assertTrue($c->check('act_on'));
        self::assertTrue($c->check('store_only'));
        self::assertFalse($c->check('both'));
        self::assertFalse($c->check(1));   // strict in_array: an int is not in a string list
    }

    public function test_enum_rules_from_example(): void
    {
        self::assertSame(['in:a,b'], (new Enum(['a', 'b']))->laravelRules());
    }

    public function test_enum_int_values_are_stringified_in_the_rule_but_checked_strictly(): void
    {
        $c = new Enum([1, 2]);

        self::assertSame(['in:1,2'], $c->laravelRules());
        self::assertTrue($c->check(1));
        self::assertTrue($c->check(2));
        self::assertFalse($c->check('1')); // strict in_array: a string is not in an int list
    }

    // StringValue ---------------------------------------------------------

    public function test_string_value_bare(): void
    {
        $c = new StringValue;

        self::assertSame('StringValue', $c->name());
        self::assertSame(['string'], $c->laravelRules());
        self::assertTrue($c->check('anything'));
        self::assertFalse($c->check(42));
        self::assertFalse($c->check(null));
    }

    public function test_string_value_with_length_bounds(): void
    {
        $c = new StringValue(2, 10);   // plan §1: min/max length -> 'min:','max:'

        self::assertSame(['string', 'min:2', 'max:10'], $c->laravelRules());
        self::assertTrue($c->check('ab'));
        self::assertTrue($c->check('abcdefghij'));
        self::assertFalse($c->check('a'));
        self::assertFalse($c->check('abcdefghijk'));
    }

    // DefaultValue --------------------------------------------------------

    public function test_default_value(): void
    {
        $c = new DefaultValue(0);

        self::assertSame('DefaultValue', $c->name());
        self::assertSame(['sometimes'], $c->laravelRules()); // plan §1: 'sometimes' semantics
        self::assertTrue($c->check(0));
        self::assertTrue($c->check(null));       // a default never rejects
        self::assertTrue($c->check('anything'));
    }

    // Range ---------------------------------------------------------------

    public function test_range(): void
    {
        $c = new Range(0, 255);   // plan §1: numeric band -> 'between:'

        self::assertSame('Range', $c->name());
        self::assertSame(['between:0,255'], $c->laravelRules());
        self::assertTrue($c->check(0));
        self::assertTrue($c->check(100));
        self::assertTrue($c->check(255));
        self::assertFalse($c->check(-1));
        self::assertFalse($c->check(256));
    }

    // Indexed -------------------------------------------------------------

    public function test_indexed(): void
    {
        $c = new Indexed;

        self::assertSame('Indexed', $c->name());
        self::assertTrue($c->unique);
        self::assertSame([], $c->laravelRules()); // store-shape limit — not a field rule (base default)
        self::assertTrue($c->check(true));
    }

    public function test_indexed_non_unique(): void
    {
        self::assertFalse((new Indexed(false))->unique);
    }

    // Relation ------------------------------------------------------------

    public function test_relation(): void
    {
        $c = new Relation('UpdatePeer', 'peer_id');

        self::assertSame('Relation', $c->name());
        self::assertSame('UpdatePeer', $c->target);
        self::assertSame('peer_id', $c->localKey);
        self::assertSame([], $c->laravelRules()); // knowledge-graph edge — no field rule in plan §1
        self::assertTrue($c->check(1));
    }

    // Unique --------------------------------------------------------------

    public function test_unique(): void
    {
        $c = new Unique(['account_id', 'peer_type', 'peer_id']);

        self::assertSame('Unique', $c->name());
        self::assertSame([], $c->laravelRules()); // entity-level limit -> migration index, not a field rule
        self::assertTrue($c->check('irrelevant')); // entity-level: the value predicate is not its job (base default)
    }

    // Sweep ---------------------------------------------------------------

    public function test_all_constraint_instances_carry_non_empty_names_and_descriptions(): void
    {
        $constraints = [
            new Required,
            new Integer,
            new Integer(0, 255),
            new Enum(['a', 'b']),
            new StringValue,
            new StringValue(1, 5),
            new DefaultValue(0),
            new Range(0, 10),
            new Indexed,
            new Indexed(false),
            new Relation('UpdatePeer', 'peer_id'),
            new Unique(['account_id']),
        ];

        foreach ($constraints as $c) {
            self::assertInstanceOf(Constraint::class, $c);
            self::assertIsString($c->name(), $c::class);
            self::assertNotSame('', $c->name(), $c::class);
            self::assertIsString($c->description(), $c::class);
            self::assertNotSame('', $c->description(), $c::class);
        }
    }
}
