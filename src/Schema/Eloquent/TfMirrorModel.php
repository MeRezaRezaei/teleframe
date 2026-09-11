<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * Base class for generated NF5 mirror parent tables.
 * Composite natural key (account_id + TL id); Eloquent treats 'id' as key
 * and AccountScoped guards account isolation. No timestamps (state projection).
 */
abstract class TfMirrorModel extends Model
{
    public $incrementing = false;
    protected $keyType = 'int';
    protected $primaryKey = 'id';
    public $timestamps = false;
}
