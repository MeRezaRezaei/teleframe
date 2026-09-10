<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockAuthorDate;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockBlockquote;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockBlockquoteBlocks;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockDetails;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockFooter;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockHeader;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockHeading1;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockHeading2;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockHeading3;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockHeading4;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockHeading5;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockHeading6;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockKicker;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockParagraph;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockPreformatted;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockPullquote;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockRelatedArticles;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockSubheader;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockSubtitle;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockTable;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockThinking;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockTitle;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageCaptionPageCaption;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageListItemPageListItemText;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageListOrderedItemPageListOrderedItemText;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageTableCellPageTableCell;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextAnchor;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextAutoEmail;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextAutoPhone;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextAutoUrl;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextBankCard;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextBold;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextBotCommand;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextCashtag;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextDate;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextEmail;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextFixed;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextHashtag;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextItalic;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextMarked;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextMention;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextMentionName;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextPhone;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextSpoiler;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextStrike;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextSubscript;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextSuperscript;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextUnderline;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextUrl;

/** Anchor model for TL type RichText (spec §4.1). */
final class TlRichText extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_rich_text_text_anchor';

    protected $guarded = [];

    public function author(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockAuthorDate::class, 'author');
    }
    public function caption(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockBlockquote::class, 'caption');
    }
    public function captionPageBlockBlockquoteBlocks(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockBlockquoteBlocks::class, 'caption');
    }
    public function captionPageBlockPullquote(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockPullquote::class, 'caption');
    }
    public function credit(): HasMany
    {
        return $this->hasMany(TlPageCaptionPageCaption::class, 'credit');
    }
    public function text(): HasMany
    {
        return $this->hasMany(TlRichTextTextBold::class, 'text');
    }
    public function textPageBlockBlockquote(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockBlockquote::class, 'text');
    }
    public function textPageBlockFooter(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockFooter::class, 'text');
    }
    public function textPageBlockHeader(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockHeader::class, 'text');
    }
    public function textPageBlockHeading1(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockHeading1::class, 'text');
    }
    public function textPageBlockHeading2(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockHeading2::class, 'text');
    }
    public function textPageBlockHeading3(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockHeading3::class, 'text');
    }
    public function textPageBlockHeading4(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockHeading4::class, 'text');
    }
    public function textPageBlockHeading5(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockHeading5::class, 'text');
    }
    public function textPageBlockHeading6(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockHeading6::class, 'text');
    }
    public function textPageBlockKicker(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockKicker::class, 'text');
    }
    public function textPageBlockParagraph(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockParagraph::class, 'text');
    }
    public function textPageBlockPreformatted(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockPreformatted::class, 'text');
    }
    public function textPageBlockPullquote(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockPullquote::class, 'text');
    }
    public function textPageBlockSubheader(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockSubheader::class, 'text');
    }
    public function textPageBlockSubtitle(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockSubtitle::class, 'text');
    }
    public function textPageBlockThinking(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockThinking::class, 'text');
    }
    public function textPageBlockTitle(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockTitle::class, 'text');
    }
    public function textPageCaption(): HasMany
    {
        return $this->hasMany(TlPageCaptionPageCaption::class, 'text');
    }
    public function textPageListItemText(): HasMany
    {
        return $this->hasMany(TlPageListItemPageListItemText::class, 'text');
    }
    public function textPageListOrderedItemText(): HasMany
    {
        return $this->hasMany(TlPageListOrderedItemPageListOrderedItemText::class, 'text');
    }
    public function textPageTableCell(): HasMany
    {
        return $this->hasMany(TlPageTableCellPageTableCell::class, 'text');
    }
    public function textTextAnchor(): HasMany
    {
        return $this->hasMany(TlRichTextTextAnchor::class, 'text');
    }
    public function textTextAutoEmail(): HasMany
    {
        return $this->hasMany(TlRichTextTextAutoEmail::class, 'text');
    }
    public function textTextAutoPhone(): HasMany
    {
        return $this->hasMany(TlRichTextTextAutoPhone::class, 'text');
    }
    public function textTextAutoUrl(): HasMany
    {
        return $this->hasMany(TlRichTextTextAutoUrl::class, 'text');
    }
    public function textTextBankCard(): HasMany
    {
        return $this->hasMany(TlRichTextTextBankCard::class, 'text');
    }
    public function textTextBotCommand(): HasMany
    {
        return $this->hasMany(TlRichTextTextBotCommand::class, 'text');
    }
    public function textTextCashtag(): HasMany
    {
        return $this->hasMany(TlRichTextTextCashtag::class, 'text');
    }
    public function textTextDate(): HasMany
    {
        return $this->hasMany(TlRichTextTextDate::class, 'text');
    }
    public function textTextEmail(): HasMany
    {
        return $this->hasMany(TlRichTextTextEmail::class, 'text');
    }
    public function textTextFixed(): HasMany
    {
        return $this->hasMany(TlRichTextTextFixed::class, 'text');
    }
    public function textTextHashtag(): HasMany
    {
        return $this->hasMany(TlRichTextTextHashtag::class, 'text');
    }
    public function textTextItalic(): HasMany
    {
        return $this->hasMany(TlRichTextTextItalic::class, 'text');
    }
    public function textTextMarked(): HasMany
    {
        return $this->hasMany(TlRichTextTextMarked::class, 'text');
    }
    public function textTextMention(): HasMany
    {
        return $this->hasMany(TlRichTextTextMention::class, 'text');
    }
    public function textTextMentionName(): HasMany
    {
        return $this->hasMany(TlRichTextTextMentionName::class, 'text');
    }
    public function textTextPhone(): HasMany
    {
        return $this->hasMany(TlRichTextTextPhone::class, 'text');
    }
    public function textTextSpoiler(): HasMany
    {
        return $this->hasMany(TlRichTextTextSpoiler::class, 'text');
    }
    public function textTextStrike(): HasMany
    {
        return $this->hasMany(TlRichTextTextStrike::class, 'text');
    }
    public function textTextSubscript(): HasMany
    {
        return $this->hasMany(TlRichTextTextSubscript::class, 'text');
    }
    public function textTextSuperscript(): HasMany
    {
        return $this->hasMany(TlRichTextTextSuperscript::class, 'text');
    }
    public function textTextUnderline(): HasMany
    {
        return $this->hasMany(TlRichTextTextUnderline::class, 'text');
    }
    public function textTextUrl(): HasMany
    {
        return $this->hasMany(TlRichTextTextUrl::class, 'text');
    }
    public function title(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockTable::class, 'title');
    }
    public function titlePageBlockDetails(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockDetails::class, 'title');
    }
    public function titlePageBlockRelatedArticles(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockRelatedArticles::class, 'title');
    }
}
