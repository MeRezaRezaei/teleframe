<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Hand-authored NF5 mirror of the Theme union (table tf_themes).
 * Single ctor (theme) — no constructor discriminator column.
 *
 * @property int $account_id
 * @property int $id
 * @property int $access_hash
 * @property string $slug
 * @property string $title
 * @property bool $creator
 * @property bool $default
 * @property bool $for_chat
 */
final class TfTheme extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_themes';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'access_hash' => 'int',
        'creator' => 'bool',
        'default' => 'bool',
        'for_chat' => 'bool',
    ];

    public function document(): HasOne
    {
        return $this->hasOne(TfThemeDocument::class, 'id', 'id');
    }

    public function settings(): HasMany
    {
        return $this->hasMany(TfThemeSetting::class, 'id', 'id');
    }

    public function emoticon(): HasOne
    {
        return $this->hasOne(TfThemeEmoticon::class, 'id', 'id');
    }

    public function installsCount(): HasOne
    {
        return $this->hasOne(TfThemeInstallsCount::class, 'id', 'id');
    }
}
