<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueType;

/** Constructor model for secureRequiredType of SecureRequiredType (crc32 829d99da). */
final class TlSecureRequiredTypeSecureRequiredType extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_secure_required_type_secure_required_type';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'native_names' => 'bool',
        'selfie_required' => 'bool',
        'translation_required' => 'bool',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(TlSecureValueType::class, 'tl_type');
    }
}
