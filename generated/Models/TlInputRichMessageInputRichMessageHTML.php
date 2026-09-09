<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputRichMessageInputRichMessageHTMLFiles;

/** Constructor model for inputRichMessageHTML of InputRichMessage (crc32 dacb836a). */
final class TlInputRichMessageInputRichMessageHTML extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_rich_message_input_rich_message_h_t_m_l';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'rtl' => 'bool',
        'noautolink' => 'bool',
        'html' => 'string',
    ];

    public function files(): HasMany
    {
        return $this->tlChild(TlInputRichMessageInputRichMessageHTMLFiles::class);
    }
}
