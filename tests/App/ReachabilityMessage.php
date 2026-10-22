<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * A stand-in for the NF5 message mirror row: the ONLY thing the event path
 * carries is the model, so this is what `Update::fromMirror()` has to read
 * the constructor from.
 *
 * Its own file, at its PSR-4 path, so BOTH consumers reach it by autoload
 * and each test file runs standalone. It used to be declared at the bottom of
 * AppReachabilityInDaemonProcessTest.php, which made it reachable only when
 * the whole `tests/App` directory was collected into one process — a single
 * file run of AppReachabilityIntakeTest.php died with "Class
 * ReachabilityMessage not found". Precedent for one class per file outside
 * the Test suffix: tests/Store/StoreScheduleUpsertDouble.php.
 */
final class ReachabilityMessage extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'reachability_message';

    protected $guarded = [];

    public static function reachabilityMessage(int $accountId, int $id, string $text): self
    {
        $model = new self;
        $model->setAttribute('account_id', $accountId);
        $model->setAttribute('id', $id);
        $model->setAttribute('constructor', 'updateNewMessage');
        $model->setAttribute('message', $text);

        return $model;
    }
}
