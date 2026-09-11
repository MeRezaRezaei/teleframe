<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlConstructor;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;

/**
 * Spec §4 decision B: Message and MessageService are disjoint constructor
 * variants and must not share one table with nullable columns. The catalog
 * entry merges them; this splitter mechanically re-derives tf_messages_service
 * from the Layer-227 wire ctor so the split survives regeneration.
 */
final class MirrorCtorSplitter
{
    private const MESSAGE_TYPE = 'Message';
    private const SPLIT_CTOR = 'messageService';
    private const TARGET = 'tf_messages_service';

    public function __construct(private readonly TlScheme $scheme) {}

    /** @param array<string,array> $catalog */
    public function apply(array $catalog): array
    {
        $src = $this->ctor();
        if ($src === null || !isset($catalog['tf_messages'])) {
            return $catalog;
        }

        $parent = $catalog['tf_messages'];
        $paramNames = array_keys($src->params());

        $out = $catalog;
        $service = [
            'tl' => self::MESSAGE_TYPE,
            'ctors' => [self::SPLIT_CTOR],
            'base' => array_values(array_filter($parent['base'], fn (array $b) => in_array($b[0], $paramNames, true))),
            'bools' => [],
            'children' => [],
        ];

        // bools: wire ctor parameter order — the ctor is authoritative, since
        // reactions_are_possible is a service-only flag absent from the merged
        // catalog's bools list
        foreach ($src->params() as $name => $param) {
            if ($param->kind() === 'true') {
                $service['bools'][] = $name;
            }
        }

        // children: parent children ∩ ctor param names, in parent order
        foreach ($parent['children'] as $child) {
            if (in_array($child[0], $paramNames, true)) {
                $service['children'][] = $child;
            }
        }
        // append ref params not already covered as a child FK; skip params that
        // are already base columns (peer_id lives in base, not as a child FK)
        $baseNames = array_column($service['base'], 0);
        foreach ($src->params() as $name => $param) {
            if ($param->kind() !== 'ref'
                || in_array($name, array_column($service['children'], 0), true)
                || in_array($name, $baseNames, true)) {
                continue;
            }
            $service['children'][] = [$name, 'FK→' . $param->baseType()];
        }

        $out[self::TARGET] = $service;
        $out['tf_messages']['ctors'] = array_values(array_filter($parent['ctors'], fn (string $c) => $c !== self::SPLIT_CTOR));

        return $out;
    }

    private function ctor(): ?TlConstructor
    {
        $msg = $this->scheme->types()[self::MESSAGE_TYPE] ?? null;
        if ($msg === null) {
            return null;
        }
        return $msg->constructors()[self::SPLIT_CTOR] ?? null;
    }
}
