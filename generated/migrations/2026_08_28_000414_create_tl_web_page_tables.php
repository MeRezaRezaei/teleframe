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
        Schema::create('tl_web_page_web_page', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('has_large_media')->default(false);
            $table->boolean('video_cover_photo')->default(false);
            $table->bigInteger('tl_id')->nullable();
            $table->text('url')->nullable();
            $table->text('display_url')->nullable();
            $table->integer('hash')->nullable();
            $table->text('tl_type')->nullable();
            $table->text('site_name')->nullable();
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_7f4ad6682884d82b84b5fc6f');
            $table->text('embed_url')->nullable();
            $table->text('embed_type')->nullable();
            $table->integer('embed_width')->nullable();
            $table->integer('embed_height')->nullable();
            $table->integer('duration')->nullable();
            $table->text('author')->nullable();
            $table->bigInteger('document')->nullable();
            $table->index('document', 'ix_999e4bc6d59601e702d9eaec');
            $table->bigInteger('cached_page')->nullable();
            $table->index('cached_page', 'ix_1081593d2d17b151664ca0ab');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e51c59663afd95e1c86889f2');
            $table->index('account_id', 'ix_ab2cf260bc775da0c5c1998a');
        });
        Schema::create('tl_web_page_web_page__attributes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_web_page_web_page', 'id', 'fk_dcf2b441846f196d0b0d621b')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1bed9003520362f54a3b');
            $table->index('account_id', 'ix_1fdd0685693ad36cee13bb76');
        });
        Schema::create('tl_web_page_web_page_empty', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('tl_id')->nullable();
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2ddecf325f639e6bbcdfd98a');
            $table->index('account_id', 'ix_af70dd9b89a9975a75ef3513');
        });
        Schema::create('tl_web_page_web_page_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('cached_page_views')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_471505538ace80071ee912ef');
            $table->index('account_id', 'ix_dd24607a400dba518a48798a');
        });
        Schema::create('tl_web_page_web_page_pending', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('tl_id')->nullable();
            $table->text('url')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_82d3939396fd545525ec5692');
            $table->index('account_id', 'ix_de773a7156f06568d86b19ca');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_web_page_web_page_pending');
        Schema::dropIfExists('tl_web_page_web_page_not_modified');
        Schema::dropIfExists('tl_web_page_web_page_empty');
        Schema::dropIfExists('tl_web_page_web_page__attributes');
        Schema::dropIfExists('tl_web_page_web_page');
    }
};
