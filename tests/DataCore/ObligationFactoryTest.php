<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\DataCore;

use MeRezaRezaei\Teleframe\DataCore\Declaration\DataDeclaration;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Field;
use MeRezaRezaei\Teleframe\DataCore\Declarations\RoutingRuleDeclaration;
use MeRezaRezaei\Teleframe\DataCore\Obligation\EventObligation;
use MeRezaRezaei\Teleframe\DataCore\Obligation\Obligation;
use MeRezaRezaei\Teleframe\DataCore\Obligation\ObligationFactory;
use MeRezaRezaei\Teleframe\DataCore\Obligation\PersistenceObligation;
use MeRezaRezaei\Teleframe\DataCore\Obligation\RequestObligation;
use MeRezaRezaei\Teleframe\DataCore\Obligation\ValidationObligation;
use PHPUnit\Framework\TestCase;

/**
 * Pins ObligationFactory::for() (plan §2): "data plus constraints makes the
 * core obligations", derived, deterministic, table-driven. UpdateRoutingRule
 * must derive ALL FOUR obligations; a constraint-free declaration derives
 * NONE; two derivations of the same declaration are identical.
 *
 * observable()/intake() are pure string-shape heuristics implemented by a
 * sibling; this suite pins the plan-guaranteed OUTCOME (a routing rule is
 * observable => notify, and an intake surface => request) rather than the
 * heuristics' internals.
 */
final class ObligationFactoryTest extends TestCase
{
    public function test_routing_rule_derives_all_four_obligations(): void
    {
        $obligations = (new ObligationFactory)->for(RoutingRuleDeclaration::define());

        self::assertCount(
            4,
            $obligations,
            'UpdateRoutingRule: limiting fields + entity Unique + observable rule + intake surface => all four kinds',
        );

        self::assertSame(['validate', 'persist', 'notify', 'request'], array_column($obligations, 'kind'));
        self::assertSame(
            ['UpdateRoutingRule', 'UpdateRoutingRule', 'UpdateRoutingRule', 'UpdateRoutingRule'],
            array_column($obligations, 'declaration'),
        );

        $expectedClasses = [
            ValidationObligation::class,
            PersistenceObligation::class,
            EventObligation::class,
            RequestObligation::class,
        ];
        foreach ($obligations as $i => $obligation) {
            self::assertInstanceOf(Obligation::class, $obligation);
            self::assertInstanceOf($expectedClasses[$i], $obligation, "obligation {$i} kind {$obligation->kind}");
            self::assertIsString($obligation->rationale, "kind {$obligation->kind} rationale");
            self::assertNotSame('', $obligation->rationale, "kind {$obligation->kind} carries a non-empty rationale");
        }
    }

    public function test_constraint_free_declaration_derives_no_obligations(): void
    {
        $declaration = new DataDeclaration(
            'NoLimits',
            'core:NoLimits',
            'nothing is limited, nothing observed, nothing ingested',
            ['x' => new Field('x', 'string')],
        );

        // No field limits => no validate; no entityConstraints => no persist;
        // name without 'Rule' suffix and no 'mode' field => no notify;
        // source not 'tl-mirror' and no 'peer_id' field => no request.
        self::assertSame([], (new ObligationFactory)->for($declaration));
    }

    public function test_derivation_is_deterministic(): void
    {
        $factory = new ObligationFactory;
        $declaration = RoutingRuleDeclaration::define();

        $first = self::obligationRows($factory->for($declaration));
        $second = self::obligationRows($factory->for($declaration));

        self::assertSame($first, $second);
        self::assertSame(['validate', 'persist', 'notify', 'request'], array_column($first, 'kind'));
        self::assertSame(
            ['UpdateRoutingRule', 'UpdateRoutingRule', 'UpdateRoutingRule', 'UpdateRoutingRule'],
            array_column($first, 'declaration'),
        );
    }

    /**
     * The three knowledge-graph columns, as a comparable row — associative
     * so array_column($rows, 'kind') / 'declaration' resolve (numeric rows
     * would make array_column return []).
     *
     * @param  list<Obligation>  $obligations
     * @return list<array{kind: string, declaration: string, rationale: string}>
     */
    private static function obligationRows(array $obligations): array
    {
        return array_map(
            static fn (Obligation $o): array => ['kind' => $o->kind, 'declaration' => $o->declaration, 'rationale' => $o->rationale],
            $obligations,
        );
    }
}
