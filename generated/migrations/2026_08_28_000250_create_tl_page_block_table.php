<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tl_page_block', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_page_block_input_page_block_map', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('geo');
            $table->integer('zoom');
            $table->integer('w');
            $table->integer('h');
            $table->uuid('caption');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_anchor', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->text('name');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_audio', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->bigInteger('audio_id');
            $table->uuid('caption');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_author_date', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('author');
            $table->integer('published_date');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_blockquote', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('text');
            $table->uuid('caption');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_blockquote_blocks', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('caption');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_blockquote_blocks__blocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_page_block_page_block_blockquote_blocks')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_7b3df98327b38690e4a6');
        });
        Schema::create('tl_page_block_page_block_channel', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('channel');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_collage', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('caption');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_collage__items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_page_block_page_block_collage')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_720d481cea6b4ab71fe7');
        });
        Schema::create('tl_page_block_page_block_cover', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('cover');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_details', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('open')->default(false);
            $table->uuid('title');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_details__blocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_page_block_page_block_details')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_859912cae7c504280b28');
        });
        Schema::create('tl_page_block_page_block_divider', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_embed', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('full_width')->default(false);
            $table->boolean('allow_scrolling')->default(false);
            $table->text('url')->nullable();
            $table->text('html')->nullable();
            $table->bigInteger('poster_photo_id')->nullable();
            $table->integer('w')->nullable();
            $table->integer('h')->nullable();
            $table->uuid('caption');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_embed_post', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->text('url');
            $table->bigInteger('webpage_id');
            $table->bigInteger('author_photo_id');
            $table->text('author');
            $table->integer('date');
            $table->uuid('caption');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_embed_post__blocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_page_block_page_block_embed_post')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_5f6d0b58edd9f985366e');
        });
        Schema::create('tl_page_block_page_block_footer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_header', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_heading1', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_heading2', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_heading3', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_heading4', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_heading5', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_heading6', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_kicker', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_list', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_list__items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_page_block_page_block_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_c20092672864acd6d10d');
        });
        Schema::create('tl_page_block_page_block_map', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('geo');
            $table->integer('zoom');
            $table->integer('w');
            $table->integer('h');
            $table->uuid('caption');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_math', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->text('source');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_ordered_list', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('reversed')->default(false);
            $table->integer('start')->nullable();
            $table->text('tl_type')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_ordered_list__items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_page_block_page_block_ordered_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_1f9b94bb9519b84a2f23');
        });
        Schema::create('tl_page_block_page_block_paragraph', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('spoiler')->default(false);
            $table->bigInteger('photo_id');
            $table->uuid('caption');
            $table->text('url')->nullable();
            $table->bigInteger('webpage_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_preformatted', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('text');
            $table->text('language');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_pullquote', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('text');
            $table->uuid('caption');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_related_articles', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('title');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_related_articles__articles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_page_block_page_block_related_articles')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_be2bc84781faf68e73f2');
        });
        Schema::create('tl_page_block_page_block_slideshow', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('caption');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_slideshow__items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_page_block_page_block_slideshow')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_6a89d8998a71271ed527');
        });
        Schema::create('tl_page_block_page_block_subheader', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_subtitle', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_table', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('bordered')->default(false);
            $table->boolean('striped')->default(false);
            $table->uuid('title');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_table__rows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_page_block_page_block_table')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_eb5f586967035f590de3');
        });
        Schema::create('tl_page_block_page_block_thinking', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_title', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_unsupported', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_page_block_page_block_video', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_block')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('autoplay')->default(false);
            $table->boolean('loop')->default(false);
            $table->boolean('spoiler')->default(false);
            $table->bigInteger('video_id');
            $table->uuid('caption');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_page_block_page_block_video');
        Schema::dropIfExists('tl_page_block_page_block_unsupported');
        Schema::dropIfExists('tl_page_block_page_block_title');
        Schema::dropIfExists('tl_page_block_page_block_thinking');
        Schema::dropIfExists('tl_page_block_page_block_table__rows');
        Schema::dropIfExists('tl_page_block_page_block_table');
        Schema::dropIfExists('tl_page_block_page_block_subtitle');
        Schema::dropIfExists('tl_page_block_page_block_subheader');
        Schema::dropIfExists('tl_page_block_page_block_slideshow__items');
        Schema::dropIfExists('tl_page_block_page_block_slideshow');
        Schema::dropIfExists('tl_page_block_page_block_related_articles__articles');
        Schema::dropIfExists('tl_page_block_page_block_related_articles');
        Schema::dropIfExists('tl_page_block_page_block_pullquote');
        Schema::dropIfExists('tl_page_block_page_block_preformatted');
        Schema::dropIfExists('tl_page_block_page_block_photo');
        Schema::dropIfExists('tl_page_block_page_block_paragraph');
        Schema::dropIfExists('tl_page_block_page_block_ordered_list__items');
        Schema::dropIfExists('tl_page_block_page_block_ordered_list');
        Schema::dropIfExists('tl_page_block_page_block_math');
        Schema::dropIfExists('tl_page_block_page_block_map');
        Schema::dropIfExists('tl_page_block_page_block_list__items');
        Schema::dropIfExists('tl_page_block_page_block_list');
        Schema::dropIfExists('tl_page_block_page_block_kicker');
        Schema::dropIfExists('tl_page_block_page_block_heading6');
        Schema::dropIfExists('tl_page_block_page_block_heading5');
        Schema::dropIfExists('tl_page_block_page_block_heading4');
        Schema::dropIfExists('tl_page_block_page_block_heading3');
        Schema::dropIfExists('tl_page_block_page_block_heading2');
        Schema::dropIfExists('tl_page_block_page_block_heading1');
        Schema::dropIfExists('tl_page_block_page_block_header');
        Schema::dropIfExists('tl_page_block_page_block_footer');
        Schema::dropIfExists('tl_page_block_page_block_embed_post__blocks');
        Schema::dropIfExists('tl_page_block_page_block_embed_post');
        Schema::dropIfExists('tl_page_block_page_block_embed');
        Schema::dropIfExists('tl_page_block_page_block_divider');
        Schema::dropIfExists('tl_page_block_page_block_details__blocks');
        Schema::dropIfExists('tl_page_block_page_block_details');
        Schema::dropIfExists('tl_page_block_page_block_cover');
        Schema::dropIfExists('tl_page_block_page_block_collage__items');
        Schema::dropIfExists('tl_page_block_page_block_collage');
        Schema::dropIfExists('tl_page_block_page_block_channel');
        Schema::dropIfExists('tl_page_block_page_block_blockquote_blocks__blocks');
        Schema::dropIfExists('tl_page_block_page_block_blockquote_blocks');
        Schema::dropIfExists('tl_page_block_page_block_blockquote');
        Schema::dropIfExists('tl_page_block_page_block_author_date');
        Schema::dropIfExists('tl_page_block_page_block_audio');
        Schema::dropIfExists('tl_page_block_page_block_anchor');
        Schema::dropIfExists('tl_page_block_input_page_block_map');
        Schema::dropIfExists('tl_page_block');
    }
};
