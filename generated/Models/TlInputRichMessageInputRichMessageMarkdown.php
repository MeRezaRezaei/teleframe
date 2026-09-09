<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputRichMessageInputRichMessageMarkdownFiles;

/** Constructor model for inputRichMessageMarkdown of InputRichMessage (crc32 004b572c). */
final class TlInputRichMessageInputRichMessageMarkdown extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_rich_message_input_rich_message_markdown';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'rtl' => 'bool',
        'noautolink' => 'bool',
        'markdown' => 'string',
    ];

    public function files(): HasMany
    {
        return $this->tlChild(TlInputRichMessageInputRichMessageMarkdownFiles::class);
    }
}
