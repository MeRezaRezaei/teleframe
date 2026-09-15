<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * NF5 mirror of the botInlineResult union (botInlineResult |
 * botInlineMediaResult) — table tf_bot_inline_results. id is the TL `string`
 * id; the required BotInlineMessage union is flattened in send_message.
 *
 * @property int $account_id
 * @property string $constructor
 * @property string $id
 * @property string $type
 */
final class TfBotInlineResult extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_bot_inline_results';

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'int',
    ];

    public function sendMessage(): HasOne
    {
        return $this->hasOne(TfBotInlineResultSendMessage::class, 'id', 'id');
    }

    public function title(): HasOne
    {
        return $this->hasOne(TfBotInlineResultTitle::class, 'id', 'id');
    }

    public function description(): HasOne
    {
        return $this->hasOne(TfBotInlineResultDescription::class, 'id', 'id');
    }

    public function url(): HasOne
    {
        return $this->hasOne(TfBotInlineResultUrl::class, 'id', 'id');
    }

    public function thumb(): HasOne
    {
        return $this->hasOne(TfBotInlineResultThumb::class, 'id', 'id');
    }

    public function content(): HasOne
    {
        return $this->hasOne(TfBotInlineResultContent::class, 'id', 'id');
    }

    /**
     * The full composite key of this row, for writing a child fact.
     *
     * @return array<string, int|string>
     */
    public function childKey(): array
    {
        return [
            'account_id' => (int) $this->account_id,
            'id' => (string) $this->id,
        ];
    }
}
