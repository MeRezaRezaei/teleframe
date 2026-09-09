<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param files (table tl_input_rich_message_input_rich_message_h_t_m_l__files). */
final class TlInputRichMessageInputRichMessageHTMLFiles extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_rich_message_input_rich_message_h_t_m_l__files';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
