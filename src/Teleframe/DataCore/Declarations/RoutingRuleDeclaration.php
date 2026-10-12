<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Declarations;

use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\DefaultValue;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\Enum;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\Integer;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\Required;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\Unique;
use MeRezaRezaei\Teleframe\DataCore\Declaration\DataDeclaration;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Field;

final class RoutingRuleDeclaration
{
    public static function define(): DataDeclaration
    {
        return new DataDeclaration(
            name: 'UpdateRoutingRule',
            source: 'core:UpdateRoutingRule',
            description: 'classifies one (account, peer) triple as act_on or store_only — loop prevention (vision spec, two-layer separation)',
            fields: [
                'account_id' => new Field('account_id', 'int', [new Required, new Integer]),
                'peer_type' => new Field('peer_type', 'int', [new Required, new Integer(0, 255)]),
                'peer_id' => new Field('peer_id', 'int', [new Required, new Integer]),
                'mode' => new Field('mode', 'string', [new Required, new Enum(['act_on', 'store_only'])]),
                'priority' => new Field('priority', 'int', [new DefaultValue(0), new Integer(0, 255)]),
            ],
            entityConstraints: [
                new Unique(['account_id', 'peer_type', 'peer_id']),  // -> the migration's unique index AND the upsert semantics
            ],
        );
    }
}
