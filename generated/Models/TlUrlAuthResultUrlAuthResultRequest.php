<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUrlAuthResultUrlAuthResultRequestMatch_codes;

/** Constructor model for urlAuthResultRequest of UrlAuthResult (crc32 3cd623ec). */
final class TlUrlAuthResultUrlAuthResultRequest extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_url_auth_result_url_auth_result_request';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'request_write_access' => 'bool',
        'request_phone_number' => 'bool',
        'match_codes_first' => 'bool',
        'is_app' => 'bool',
        'bot' => 'string',
        'domain' => 'string',
        'browser' => 'string',
        'platform' => 'string',
        'ip' => 'string',
        'region' => 'string',
        'user_id_hint' => 'int',
        'verified_app_name' => 'string',
    ];

    public function matchCodes(): HasMany
    {
        return $this->tlChild(TlUrlAuthResultUrlAuthResultRequestMatch_codes::class);
    }
}
