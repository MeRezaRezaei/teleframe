<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Control;

use Closure;

/**
 * The control-layer "accounts in action" catalog for the client shell.
 *
 * Answers "which of MY relayed accounts are live right now and what are
 * they" — the switch menu. Presence comes from the injected
 * RelaySessionRegistry (never a dial: a session exists because a relay fed
 * it), and the display identity (label, dc) for each live account id comes
 * from an injected resolver callable so this class stays plain PHP and the
 * host decides how the label lookup happens (Eloquent row, cache, ...).
 *
 * Every returned entry never contains a session string.
 *
 * @phpstan-type AccountRow array{account_id: int, label: string, dc_id: int}
 */
final class RelayAccountsCatalog
{
    /** @var Closure(list<int>): array<int, AccountRow|null> */
    private readonly Closure $resolve;

    /**
     * @param  (callable(list<int>): array<int, AccountRow|null>)|null  $resolver
     *                                                                             called with the live account ids, returns rows keyed by
     *                                                                             the account id; missing ids are simply omitted
     *                                                                             (null rows are treated as missing)
     */
    public function __construct(
        private readonly RelaySessionRegistry $registry,
        ?callable $resolver = null,
    ) {
        $this->resolve = $resolver === null
            ? static fn (array $ids): array => []
            : $resolver(...);
    }

    /** @return list<array{account_id: int, label: string, dc_id: int, online: bool}> */
    public function live(): array
    {
        $sessions = $this->registry->active();
        $ids = array_map(static fn (RelaySession $session): int => $session->accountId, $sessions);
        if ($ids === []) {
            return [];
        }

        /** @var array<int, AccountRow|null> $rows */
        $rows = ($this->resolve)($ids);
        $entries = [];
        foreach ($sessions as $session) {
            $row = $rows[$session->accountId] ?? null;
            if ($row === null) {
                continue;
            }

            $entries[] = [
                'account_id' => $session->accountId,
                'label' => $row['label'],
                'dc_id' => $row['dc_id'],
                'online' => true,
            ];
        }

        return $entries;
    }
}
