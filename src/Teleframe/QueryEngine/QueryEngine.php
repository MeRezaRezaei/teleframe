<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\QueryEngine;

use MeRezaRezaei\Telegram\MultiSession\LoopDetector\LoopDetector as BaseLoopDetector;
use MeRezaRezaei\Teleframe\Telegram\MultiSession\QueryEngine\Policy;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class QueryEngine
{
    private string $mode;

    private BaseLoopDetector $loopDetector;

    private LoggerInterface $logger;

    /** @var array<string, mixed> */
    private array $stateTransitions = [
        'channel' => 'queue',
        'group' => 'broadcast',
        'private' => 'direct',
        'user' => 'direct',
        'bot' => 'direct',
    ];

    public function __construct(
        mixed $first = null,
        ?LoggerInterface $logger = null,
        string $mode = 'auto',
        ?BaseLoopDetector $loopDetector = null,
    ) {
        if ($first instanceof BaseLoopDetector) {
            $this->loopDetector = $first;
        } elseif ($loopDetector instanceof BaseLoopDetector) {
            $this->loopDetector = $loopDetector;
        } else {
            $this->loopDetector = new BaseLoopDetector;
        }
        if ($first instanceof LoggerInterface) {
            $this->logger = $first;
        } else {
            $this->logger = $logger ?? new NullLogger;
        }
        $this->mode = $mode;
    }

    public function mode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }

    public function getLoopDetector(): BaseLoopDetector
    {
        return $this->loopDetector;
    }

    public function setLoopDetector(BaseLoopDetector $detector): void
    {
        $this->loopDetector = $detector;
    }

    /**
     * @return list<LoopPattern>
     */
    public function analyze(mixed $source): array
    {
        $bindings = $this->extractBindings($source);
        $this->logger->debug('QueryEngine analyze', ['bindings' => count($bindings)]);
        $graph = $this->buildGraph($bindings);
        $patterns = [];
        $cycles = $this->findCyclePaths($graph);
        $cycleEdges = [];
        foreach ($cycles as $cycle) {
            $n = count($cycle);
            for ($i = 0; $i < $n; $i++) {
                $cycleEdges[$cycle[$i].'->'.$cycle[($i+1)%$n]] = true;
            }
        }
        foreach ($cycles as $cycle) {
            $patterns[] = LoopPattern::mutualRecursion(
                $cycle,
                $this->recommendBreakers($cycle, $bindings),
                1.0,
                $this->bindingsForNodes($cycle, $bindings),
            );
        }
        foreach ($this->findChains($graph, $cycleEdges) as $chain) {
            $patterns[] = LoopPattern::chain(
                $chain,
                $this->recommendBreakers($chain, $bindings),
                0.5,
                $this->bindingsForNodes($chain, $bindings),
            );
        }
        foreach ($this->findBroadcasts($graph) as $node) {
            $patterns[] = LoopPattern::broadcast(
                [$node],
                [$node],
                0.4,
                $this->bindingsForNodes([$node], $bindings),
            );
        }

        return $patterns;
    }

    public function detectLoops(mixed $source): array
    {
        return $this->analyze($source);
    }

    public function detectMutualRecursion(mixed $source): array
    {
        $all = $this->analyze($source);

        return array_values(array_filter($all, static fn (LoopPattern $p): bool => $p->type === 'mutual_recursion'));
    }

    public function detectChains(mixed $source): array
    {
        $all = $this->analyze($source);

        return array_values(array_filter($all, static fn (LoopPattern $p): bool => $p->type === 'chain'));
    }

    public function detectBroadcasts(mixed $source): array
    {
        $all = $this->analyze($source);

        return array_values(array_filter($all, static fn (LoopPattern $p): bool => $p->type === 'broadcast'));
    }

    public function analyzeBindings(mixed $source): array
    {
        return $this->analyze($source);
    }

    public function scan(mixed $source): array
    {
        return $this->analyze($source);
    }

    public function check(mixed $source): array
    {
        return $this->analyze($source);
    }

    public function analyzeRegistration(mixed $registry, mixed $contract, array $preconditions = [], array $postconditions = [], bool $breakCycle = false, array $metadata = []): ?LoopPattern
    {
        // Add binding temporarily to analyze.
        if ($registry instanceof BinderRegistry) {
            $id = $registry->bind($contract, $preconditions, $postconditions, $breakCycle, $metadata);
        } elseif (is_object($registry) && method_exists($registry, 'bind')) {
            $id = $registry->bind($contract, $preconditions, $postconditions, $breakCycle, $metadata);
        } else {
            $id = null;
        }

        $patterns = $this->analyze($registry);
        $target = $this->lastPattern($patterns);

        return $target;
    }

    public function register(mixed $registry, mixed $contract, array $preconditions = [], array $postconditions = [], bool $breakCycle = false, array $metadata = []): mixed
    {
        if (! ($registry instanceof BinderRegistry) && ! (is_object($registry) && method_exists($registry, 'bind'))) {
            throw new \InvalidArgumentException('registry must support bind()');
        }
        $id = $registry->bind($contract, $preconditions, $postconditions, $breakCycle, $metadata);

        $patterns = $this->analyze($registry);
        $loop = $this->firstMutual($patterns);

        if ($loop !== null) {
            if ($this->mode === 'reject') {
                $registry->unbind($id);
                throw new LoopDetectedException('Infinite loop detected: '.implode(' -> ', $loop->cycle));
            }
            if ($this->mode === 'require_break' && ! $this->cycleHasBreaker($registry, $loop)) {
                $registry->unbind($id);
                throw new BreakCycleRequiredException('BreakCycle annotation required on one handler in cycle: '.implode(' -> ', $loop->cycle));
            }
            // auto: inject policy
            $this->injectPolicyForLoop($loop);
        }

        return $id;
    }

    public function registerBinding(mixed $registry, mixed $contract, array $preconditions = [], array $postconditions = [], bool $breakCycle = false, array $metadata = []): mixed
    {
        return $this->register($registry, $contract, $preconditions, $postconditions, $breakCycle, $metadata);
    }

    public function bind(mixed $registry, mixed $contract, array $preconditions = [], array $postconditions = [], bool $breakCycle = false, array $metadata = []): mixed
    {
        return $this->register($registry, $contract, $preconditions, $postconditions, $breakCycle, $metadata);
    }

    public function onRegister(mixed $registry, mixed $contract, array $preconditions = [], array $postconditions = [], bool $breakCycle = false, array $metadata = []): ?LoopPattern
    {
        try {
            $this->register($registry, $contract, $preconditions, $postconditions, $breakCycle, $metadata);
        } catch (\Throwable $e) {
            // ignore
        }
        $patterns = $this->analyze($registry);

        return $this->firstMutual($patterns) ?? ($patterns[0] ?? null);
    }

    public function preventLoop(LoopPattern $pattern, string $strategy = 'auto'): void
    {
        if ($strategy === 'reject') {
            throw new LoopDetectedException('Loop prevented: '.implode(' -> ', $pattern->cycle));
        }
        $this->injectPolicyForPattern($pattern);
    }

    public function injectPolicy(string|array $actionIds, Policy $policy, ?BaseLoopDetector $detector = null): void
    {
        $detector = $detector ?? $this->loopDetector;
        $ids = is_array($actionIds) ? $actionIds : [$actionIds];
        foreach ($ids as $id) {
            $detector->applyPolicy((string) $id, $policy);
        }
    }

    public function injectPolicyForLoop(LoopPattern $pattern): void
    {
        $policy = new Policy(cooldownMs: 1000, dedupWindowMs: 5000, breakCycles: true, cycleBreakers: $pattern->breakers);
        $this->injectPolicy($pattern->breakers !== [] ? $pattern->breakers : $pattern->cycle, $policy);
    }

    public function injectPolicyForPattern(LoopPattern $pattern): void
    {
        $this->injectPolicyForLoop($pattern);
    }

    public function applyPolicy(string|array $actionIds, Policy $policy, ?BaseLoopDetector $detector = null): void
    {
        $this->injectPolicy($actionIds, $policy, $detector);
    }

    public function requireBreakCycle(LoopPattern $pattern, BinderRegistry $registry): bool
    {
        foreach ($pattern->cycle as $node) {
            // check if any binding in cycle has breakCycle
        }
        foreach ($registry->allBindings() as $id => $contract) {
            if (in_array($this->nodeKey($contract), $pattern->cycle, true) && $registry->hasBreakCycle($id)) {
                return true;
            }
        }

        return false;
    }

    public function cycleHasBreaker(BinderRegistry $registry, LoopPattern $pattern): bool
    {
        return $this->requireBreakCycle($pattern, $registry);
    }

    public function stateTransition(string $chatType): string
    {
        $key = strtolower($chatType);

        return $this->stateTransitions[$key] ?? 'direct';
    }

    public function conversationPattern(string $chatType): string
    {
        return $this->stateTransition($chatType);
    }

    public function chatTypeToPattern(string $chatType): string
    {
        return $this->stateTransition($chatType);
    }

    public function patternForChatType(string $chatType): string
    {
        return $this->stateTransition($chatType);
    }

    public function stateForChatType(string $chatType): string
    {
        return $this->stateTransition($chatType);
    }

    public function setStateTransition(string $chatType, string $pattern): void
    {
        $this->stateTransitions[strtolower($chatType)] = $pattern;
    }

    // --- internals ---

    /**
     * @return list<array{id: string, contract: mixed, preconditions: array, postconditions: array, breakCycle: bool, metadata: array}>
     */
    private function extractBindings(mixed $source): array
    {
        $rows = [];
        if ($source instanceof BinderRegistry) {
            foreach ($source->getAllBindings() as $id => $contract) {
                $rows[] = [
                    'id' => (string) $id,
                    'contract' => $contract,
                    'preconditions' => $source->getPreconditions($id),
                    'postconditions' => $source->getPostconditions($id),
                    'breakCycle' => $source->hasBreakCycle($id),
                    'metadata' => $source->getBindingData($id)['metadata'] ?? [],
                ];
            }

            return $rows;
        }
        if (is_array($source)) {
            foreach ($source as $key => $item) {
                if ($item instanceof Binding) {
                    $rows[] = [
                        'id' => (string) ($item->id ?? $key),
                        'contract' => $item->contract,
                        'preconditions' => $item->preconditions,
                        'postconditions' => $item->postconditions,
                        'breakCycle' => $item->breakCycle,
                        'metadata' => $item->metadata,
                    ];
                } elseif (is_object($item) && method_exists($item, 'getQuery')) {
                    $rows[] = [
                        'id' => (string) $key,
                        'contract' => $item,
                        'preconditions' => [],
                        'postconditions' => [],
                        'breakCycle' => false,
                        'metadata' => [],
                    ];
                } elseif (is_array($item)) {
                    $rows[] = [
                        'id' => (string) $key,
                        'contract' => $item['contract'] ?? $item,
                        'preconditions' => $item['preconditions'] ?? [],
                        'postconditions' => $item['postconditions'] ?? [],
                        'breakCycle' => (bool) ($item['breakCycle'] ?? false),
                        'metadata' => $item['metadata'] ?? [],
                    ];
                }
            }

            return $rows;
        }
        if (is_object($source) && method_exists($source, 'getAllBindings')) {
            $all = $source->getAllBindings();
            return $this->extractBindings($all);
        }
        if (is_object($source) && method_exists($source, 'allBindings')) {
            $all = $source->allBindings();
            return $this->extractBindings($all);
        }

        return $rows;
    }

    /**
     * @param list<array{id: string, contract: mixed, preconditions: array, postconditions: array, breakCycle: bool, metadata: array}> $bindings
     * @return array<string, list<string>>
     */
    private function buildGraph(array $bindings): array
    {
        $graph = [];
        $nodes = [];
        foreach ($bindings as $b) {
            $node = $this->nodeKey($b['contract']);
            if ($node === '') {
                $node = $b['id'];
            }
            $nodes[$b['id']] = $node;
        }

        // adjacency by binding id
        foreach ($bindings as $a) {
            $from = $a['id'];
            $graph[$from] = $graph[$from] ?? [];
            $aEmits = $this->emittedKeys($a);
            foreach ($bindings as $b) {
                if ($a['id'] === $b['id']) {
                    continue;
                }
                if ($this->triggers($b, $aEmits)) {
                    if (! in_array($b['id'], $graph[$from], true)) {
                        $graph[$from][] = $b['id'];
                    }
                }
            }
        }

        return $graph;
    }

    private function triggers(array $binding, array $emitted): bool
    {
        $triggers = $this->triggerKeys($binding);
        if ($triggers === [] || $emitted === []) {
            return false;
        }
        foreach ($triggers as $t) {
            foreach ($emitted as $e) {
                if ($t === $e || str_contains($e, $t) || str_contains($t, $e)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return list<string> */
    private function triggerKeys(array $binding): array
    {
        $keys = [];
        $contract = $binding['contract'];
        foreach ($binding['preconditions'] as $p) {
            $keys[] = is_string($p) ? $p : (string) json_encode($p);
        }
        $query = $this->getQuery($contract);
        if ($query !== null) {
            $keys[] = (string) ($query->type ?? '');
            foreach (($query->params ?? []) as $k => $v) {
                $keys[] = $k.':'.$v;
            }
        }

        return array_values(array_unique(array_filter($keys, static fn ($v): bool => $v !== '')));
    }

    /** @return list<string> */
    private function emittedKeys(array $binding): array
    {
        $keys = [];
        $contract = $binding['contract'];
        foreach ($binding['postconditions'] as $p) {
            $keys[] = is_string($p) ? $p : (string) json_encode($p);
        }
        $action = $this->getAction($contract);
        if ($action !== null) {
            $keys[] = (string) ($action->handlerClass ?? '');
            foreach (($action->payload ?? []) as $k => $v) {
                if (is_scalar($v)) {
                    $keys[] = $k.':'.$v;
                }
            }
        }

        return array_values(array_unique(array_filter($keys, static fn ($v): bool => $v !== '')));
    }

    private function nodeKey(mixed $contract): string
    {
        if (is_string($contract)) {
            return $contract;
        }
        if (is_array($contract)) {
            return (string) ($contract['handler'] ?? $contract['name'] ?? $contract['id'] ?? '');
        }
        if (is_object($contract)) {
            if (method_exists($contract, 'getAction')) {
                $action = $contract->getAction();
                if (is_object($action) && isset($action->handlerClass)) {
                    return (string) $action->handlerClass;
                }
            }
            if (isset($contract->handlerClass)) {
                return (string) $contract->handlerClass;
            }
            if (method_exists($contract, 'getQuery')) {
                $q = $contract->getQuery();
                if (is_object($q) && isset($q->type)) {
                    return (string) $q->type;
                }
            }
            if (isset($contract->type)) {
                return (string) $contract->type;
            }
        }

        return '';
    }

    private function getQuery(mixed $contract): ?object
    {
        if (is_object($contract) && method_exists($contract, 'getQuery')) {
            $q = $contract->getQuery();

            return is_object($q) ? $q : null;
        }

        return null;
    }

    private function getAction(mixed $contract): ?object
    {
        if (is_object($contract) && method_exists($contract, 'getAction')) {
            $a = $contract->getAction();

            return is_object($a) ? $a : null;
        }

        return null;
    }

    /**
     * @param array<string, list<string>> $graph
     * @return list<list<string>>
     */
    private function findCyclePaths(array $graph): array
    {
        $cycles = [];
        $nodes = array_keys($graph);
        foreach ($nodes as $start) {
            $this->dfsCycles($graph, $start, $start, [], $cycles, []);
        }
        // dedup cycles by canonical rotation (also collapses reverse duplicates)
        $unique = [];
        $seen = [];
        foreach ($cycles as $c) {
            $rotations = [];
            $n = count($c);
            for ($i = 0; $i < $n; $i++) {
                $rot = array_merge(array_slice($c, $i), array_slice($c, 0, $i));
                $rotations[] = implode('->', $rot);
            }
            $key = min($rotations);
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $c;
            }
        }

        return $unique;
    }

    private function dfsCycles(array $graph, string $start, string $current, array $path, array &$cycles, array $visited): void
    {
        foreach (($graph[$current] ?? []) as $next) {
            if ($next === $start && count($path) >= 1) {
                $cycles[] = array_merge([$start], $path);
                continue;
            }
            if (in_array($next, $path, true) || $next === $start) {
                // skip re-visiting
            }
            if (! in_array($next, $path, true) && $next !== $start) {
                $path2 = $path;
                $path2[] = $next;
                $this->dfsCycles($graph, $start, $next, $path2, $cycles, $visited);
            }
        }
    }

    /**
     * @param array<string, list<string>> $graph
     * @return list<list<string>>
     */
    private function findChains(array $graph, array $cycleEdges = []): array
    {
        // Chains: paths of length >=2 that are not part of a cycle.
        $chains = [];
        foreach (array_keys($graph) as $start) {
            // simple path exploration up to 4 nodes
            $this->exploreChains($graph, $start, [$start], $chains);
        }
        // remove trivial self and cycles
        $result = [];
        $seen = [];
        // filter chains that use a cycle edge
        $filtered = [];
        foreach ($chains as $c) {
            if (count($c) < 3) {
                continue;
            }
            $usesCycle = false;
            for ($i = 0; $i < count($c) - 1; $i++) {
                if (isset($cycleEdges[$c[$i].'->'.$c[$i+1]])) {
                    $usesCycle = true;
                    break;
                }
            }
            if ($usesCycle) {
                continue;
            }
            $filtered[] = $c;
        }
        // keep only maximal chains (not a prefix of another)
        foreach ($filtered as $c) {
            $key = implode('->', $c);
            $maximal = true;
            foreach ($filtered as $other) {
                if ($other === $c) {
                    continue;
                }
                if (count($other) <= count($c)) {
                    continue;
                }
                // contained as contiguous subsequence
                for ($i = 0; $i <= count($other) - count($c); $i++) {
                    if (array_slice($other, $i, count($c)) === $c) {
                        $maximal = false;
                        break 2;
                    }
                }
            }
            if (! $maximal) {
                continue;
            }
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $c;
        }

        return $result;
    }

    private function exploreChains(array $graph, string $current, array $path, array &$chains): void
    {
        if (count($path) >= 2 && count($path) <= 4) {
            $chains[] = $path;
        }
        if (count($path) >= 4) {
            return;
        }
        foreach (($graph[$current] ?? []) as $next) {
            if (in_array($next, $path, true)) {
                continue; // cycle handled elsewhere
            }
            $path2 = $path;
            $path2[] = $next;
            $this->exploreChains($graph, $next, $path2, $chains);
        }
    }

    /**
     * @param array<string, list<string>> $graph
     * @return list<string>
     */
    private function findBroadcasts(array $graph): array
    {
        $broadcasts = [];
        foreach ($graph as $node => $edges) {
            if (count($edges) > 1) {
                $broadcasts[] = $node;
            }
        }

        return $broadcasts;
    }

    /** @return list<string> */
    private function recommendBreakers(array $cycle, array $bindings): array
    {
        $breakers = [];
        foreach ($bindings as $b) {
            if (in_array($b['id'], $cycle, true) && $b['breakCycle']) {
                $breakers[] = $b['id'];
            }
        }
        if ($breakers === []) {
            // recommend last node
            $breakers = [end($cycle) ?: ''];
        }

        return array_values(array_filter($breakers));
    }

    /** @param list<string> $nodes
     *  @param list<array{id: string, ...}> $bindings
     *  @return list<mixed>
     */
    private function bindingsForNodes(array $nodes, array $bindings): array
    {
        $result = [];
        foreach ($bindings as $b) {
            if (in_array($b['id'], $nodes, true)) {
                $result[] = $b['contract'];
            }
        }

        return $result;
    }

    private function firstMutual(array $patterns): ?LoopPattern
    {
        foreach ($patterns as $p) {
            if ($p instanceof LoopPattern && $p->type === 'mutual_recursion') {
                return $p;
            }
        }

        return null;
    }

    private function lastPattern(array $patterns): ?LoopPattern
    {
        if ($patterns === []) {
            return null;
        }

        return $patterns[array_key_last($patterns)];
    }


    public function findCycles(mixed $source): array { return $this->detectMutualRecursion($source); }
    public function detect(mixed $source): array { return $this->analyze($source); }
    public function findLoops(mixed $source): array { return $this->analyze($source); }
    public function findCyclesPublic(mixed $source): array { return $this->detectMutualRecursion($source); }
    public function getPatterns(mixed $source): array { return $this->analyze($source); }
    public function checkLoops(mixed $source): array { return $this->analyze($source); }

}
