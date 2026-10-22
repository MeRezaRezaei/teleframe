<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\DataCore;

use LogicException;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\Unique;
use MeRezaRezaei\Teleframe\DataCore\Declaration\DataDeclaration;
use MeRezaRezaei\Teleframe\DataCore\Declaration\DeclarationRegistry;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Field;
use MeRezaRezaei\Teleframe\DataCore\Declarations\RoutingRuleDeclaration;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Pins DeclarationRegistry (plan §1: "name => DataDeclaration; the
 * knowledge-graph the verbatim asks for") and the exact
 * RoutingRuleDeclaration::define() shape of plan §2 — five fields with
 * their exact constraint payloads, plus the entity-level
 * Unique(['account_id','peer_type','peer_id']). §4's generated rule-map
 * for UpdateRoutingRule is cross-checked at the end.
 */
final class DeclarationRegistryTest extends TestCase
{
    // RoutingRuleDeclaration::define() — the exact plan §2 shape -----------

    public function test_routing_rule_declaration_exact_shape(): void
    {
        $d = RoutingRuleDeclaration::define();

        self::assertSame('UpdateRoutingRule', $d->name);
        self::assertSame('core:UpdateRoutingRule', $d->source);
        self::assertSame(
            'classifies one (account, peer) triple as act_on or store_only — loop prevention (vision spec, two-layer separation)',
            $d->description,
        );
        self::assertCount(5, $d->fields);
        self::assertSame(
            ['account_id', 'peer_type', 'peer_id', 'mode', 'priority'],
            array_keys($d->fields),
        );

        // [phpType, flattened constraint rules] — plan §2 payloads.
        // 'priority' includes DefaultValue's 'sometimes' (plan §1 semantics);
        // §4's generated map drops it (see the dedicated test below).
        $expected = [
            'account_id' => ['int', ['required', 'integer']],
            'peer_type' => ['int', ['required', 'integer', 'between:0,255']],
            'peer_id' => ['int', ['required', 'integer']],
            'mode' => ['string', ['required', 'in:act_on,store_only']],
            'priority' => ['int', ['sometimes', 'integer', 'between:0,255']],
        ];

        foreach ($expected as $name => [$phpType, $rules]) {
            $field = $d->field($name);
            self::assertInstanceOf(Field::class, $field, $name);
            self::assertSame($name, $field->name);
            self::assertSame($phpType, $field->phpType, $name);
            self::assertTrue($field->required, $name);
            self::assertSame($rules, self::flattenRules($field), $name);
        }
    }

    public function test_routing_rule_field_constraint_kinds(): void
    {
        $d = RoutingRuleDeclaration::define();

        $expected = [
            'account_id' => ['Required', 'Integer'],
            'peer_type' => ['Required', 'Integer'],
            'peer_id' => ['Required', 'Integer'],
            'mode' => ['Required', 'Enum'],
            'priority' => ['DefaultValue', 'Integer'],
        ];

        foreach ($expected as $name => $kinds) {
            $field = $d->field($name);
            self::assertInstanceOf(Field::class, $field, $name);
            $actual = array_map(
                static fn ($constraint): string => $constraint->name(),
                $field->constraints,
            );
            self::assertSame($kinds, $actual, $name);
        }
    }

    public function test_routing_rule_entity_unique(): void
    {
        $d = RoutingRuleDeclaration::define();

        self::assertCount(1, $d->entityConstraints);
        $unique = $d->entityConstraints[0];
        self::assertInstanceOf(Unique::class, $unique);
        self::assertSame('Unique', $unique->name());
        self::assertSame(['account_id', 'peer_type', 'peer_id'], self::uniqueColumns($unique));
    }

    // §4 cross-check: the generated UpdateRoutingRuleValidator::rules() ----

    public function test_generated_rule_map_matches_plan_section4(): void
    {
        // Plan §4's exact generated map. The declaration flatten carries
        // DefaultValue's 'sometimes' (a plan §1 semantic marker), but the
        // GENERATED map treats a default as a data-shape concern, not a
        // validator rule — so the bridge is: flatten, then drop 'sometimes'.
        // Everything else must match byte-for-byte.
        $expected = [
            'account_id' => ['required', 'integer'],
            'peer_type' => ['required', 'integer', 'between:0,255'],
            'peer_id' => ['required', 'integer'],
            'mode' => ['required', 'in:act_on,store_only'],
            'priority' => ['integer', 'between:0,255'],
        ];

        $d = RoutingRuleDeclaration::define();
        foreach ($expected as $name => $rules) {
            $field = $d->field($name);
            self::assertInstanceOf(Field::class, $field, $name);
            $flattened = array_values(array_filter(
                self::flattenRules($field),
                static fn (string $rule): bool => $rule !== 'sometimes',
            ));
            self::assertSame($rules, $flattened, "generated rule map for {$name}");
        }
    }

    // DeclarationRegistry --------------------------------------------------

    public function test_registry_round_trip(): void
    {
        $declaration = RoutingRuleDeclaration::define();
        $registry = DeclarationRegistry::fromDeclarations([$declaration]);

        self::assertSame(['UpdateRoutingRule' => $declaration], $registry->all());
        self::assertSame($declaration, $registry->get('UpdateRoutingRule'));
    }

    public function test_duplicate_name_rejected(): void
    {
        $a = new DataDeclaration('Same', 'core:a', 'first', [], []);
        $b = new DataDeclaration('Same', 'core:b', 'second', [], []);

        $this->expectException(LogicException::class);

        DeclarationRegistry::fromDeclarations([$a, $b]);
    }

    public function test_field_lookup_through_registry(): void
    {
        $registry = DeclarationRegistry::fromDeclarations([RoutingRuleDeclaration::define()]);
        $d = $registry->get('UpdateRoutingRule');

        self::assertSame('mode', $d->field('mode')?->name);
        self::assertSame('int', $d->field('peer_id')?->phpType);
        self::assertNull($d->field('does_not_exist'));
    }

    // Helpers --------------------------------------------------------------

    /** Concatenate a field's constraints into one per-field rule list. */
    private static function flattenRules(Field $field): array
    {
        $rules = [];
        foreach ($field->constraints as $constraint) {
            $rules = array_merge($rules, $constraint->laravelRules());
        }

        return $rules;
    }

    /**
     * The Unique column list, read from the first declared property so the
     * assertion holds whatever readonly property name the sibling chose
     * (`$fields`, `$columns`, …) — plan §2 pins the payload, not the prop.
     *
     * @return list<string>
     */
    private static function uniqueColumns(Unique $unique): array
    {
        $properties = (new ReflectionClass($unique))->getProperties();
        self::assertNotEmpty($properties, 'Unique must carry its column list in a (readonly) property');
        $columns = $properties[0]->getValue($unique);
        self::assertIsArray($columns, 'Unique\'s column list property must hold an array');

        return $columns;
    }
}
