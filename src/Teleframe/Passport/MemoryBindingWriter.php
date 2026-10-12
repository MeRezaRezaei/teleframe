<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * In-memory binding writer — array/fake impl (tests, unconfigured, plain-PHP).
 *
 * The inert default posture (design §1 — every class constructs with
 * memory/fake seams): records binding rows in a plain array instead of
 * Eloquent, so `PassportLoginService` is fully testable and the unconfigured
 * module is byte-for-byte silent until someone actually calls login(). Zero
 * container, zero Eloquent, zero facade calls.
 *
 * Row shape mirrors the `tl_user_bindings` columns the real writer touches:
 * `['tg_id' => int, 'account_id' => ?int, 'user' => object]`. `last()` returns
 * null when nothing has been bound yet.
 *
 * Implements `PassportProvenanceRecorder` so tests can assert what the real
 * writer would have stamped, in memory, with no database: `provenance()` hands
 * back the recorded (tg id, account) => ['fields' => list<string>, 'at' => int]
 * ledger. As on the Eloquent side the ledger holds SecureValueType NAMES only
 * — the memory writer enforces the same allow-list the model does, so a test
 * that wrongly passed a decrypted value sees it dropped rather than stored.
 */
final class MemoryBindingWriter implements BindingWriter, PassportProvenanceRecorder
{
    /** @var list<array{tg_id: int, account_id: ?int, user: object}> */
    private array $rows = [];

    /** @var array<string, array{fields: list<string>, at: int}> keyed "tg:account" */
    private array $provenance = [];

    public function bind(int $tgId, ?int $accountId, object $user): mixed
    {
        $row = ['tg_id' => $tgId, 'account_id' => $accountId, 'user' => $user];

        $this->rows[] = $row;

        return $row;
    }

    /**
     * @param  list<string>  $fieldsVerified
     */
    public function recordProvenance(int $tgId, ?int $accountId, array $fieldsVerified): void
    {
        $types = [];
        foreach (['personal_details', 'email', 'phone'] as $type) {
            if (in_array($type, $fieldsVerified, true)) {
                $types[] = $type;
            }
        }

        $this->provenance[$tgId.':'.($accountId ?? 'null')] = [
            'fields' => $types,
            'at' => time(),
        ];
    }

    /**
     * The recorded provenance for a (tg id, account) pair, or null when no
     * passport login has been recorded for it.
     *
     * @return array{fields: list<string>, at: int}|null
     */
    public function provenance(int $tgId, ?int $accountId = null): ?array
    {
        return $this->provenance[$tgId.':'.($accountId ?? 'null')] ?? null;
    }

    /** @return list<array{tg_id: int, account_id: ?int, user: object}> */
    public function rows(): array
    {
        return $this->rows;
    }

    /** @return array{tg_id: int, account_id: ?int, user: object}|null */
    public function last(): ?array
    {
        return $this->rows === [] ? null : $this->rows[array_key_last($this->rows)];
    }
}
