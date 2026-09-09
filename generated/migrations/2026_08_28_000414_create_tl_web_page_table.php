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
        Schema::create('tl_web_page', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_36b084a891651d051a2bc200');
            $table->index('account_id', 'ix_01b4899dcb3e62c4daf07971');
        });
        Schema::create('tl_web_page_web_page', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_page')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('has_large_media')->default(false);
            $table->boolean('video_cover_photo')->default(false);
            $table->bigInteger('tl_id');
            $table->text('url');
            $table->text('display_url');
            $table->integer('hash');
            $table->text('tl_type')->nullable();
            $table->text('site_name')->nullable();
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->uuid('photo')->nullable();
            $table->index('photo', 'ix_7f4ad6682884d82b84b5fc6f');
            $table->text('embed_url')->nullable();
            $table->text('embed_type')->nullable();
            $table->integer('embed_width')->nullable();
            $table->integer('embed_height')->nullable();
            $table->integer('duration')->nullable();
            $table->text('author')->nullable();
            $table->uuid('document')->nullable();
            $table->index('document', 'ix_999e4bc6d59601e702d9eaec');
            $table->uuid('cached_page')->nullable();
            $table->index('cached_page', 'ix_1081593d2d17b151664ca0ab');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ab2cf260bc775da0c5c1998a');
            $table->unique(['account_id', 'tl_id'], 'ux_0a86c4501428a82f10ee');
        });
        Schema::create('tl_web_page_web_page__attributes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_web_page_web_page')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1bed9003520362f54a3b');
            $table->index('account_id', 'ix_1fdd0685693ad36cee13bb76');
        });
        Schema::create('tl_web_page_web_page_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_page')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('tl_id');
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_af70dd9b89a9975a75ef3513');
            $table->unique(['account_id', 'tl_id'], 'ux_c9e5ef950cf8f91924ee');
        });
        Schema::create('tl_web_page_web_page_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_page')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('cached_page_views')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_dd24607a400dba518a48798a');
        });
        Schema::create('tl_web_page_web_page_pending', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_page')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('tl_id');
            $table->text('url')->nullable();
            $table->integer('date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_de773a7156f06568d86b19ca');
            $table->unique(['account_id', 'tl_id'], 'ux_2b7a0781acd67841d7ab');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_web_page_web_page_pending');
        Schema::dropIfExists('tl_web_page_web_page_not_modified');
        Schema::dropIfExists('tl_web_page_web_page_empty');
        Schema::dropIfExists('tl_web_page_web_page__attributes');
        Schema::dropIfExists('tl_web_page_web_page');
        Schema::dropIfExists('tl_web_page');
    }
};
