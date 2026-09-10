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
        Schema::create('tl_page_page', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('part')->default(false);
            $table->boolean('rtl')->default(false);
            $table->boolean('v2')->default(false);
            $table->text('url')->nullable();
            $table->integer('views')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0b0eb158d00eef90698183e1');
            $table->index('account_id', 'ix_0262e60a0800545932c9e9e0');
        });
        Schema::create('tl_page_page__blocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_page_page', 'id', 'fk_a47cfe449e84bf39cac78513')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a8322462e3556f64a7b6');
            $table->index('account_id', 'ix_91d3d2375d5990e9cc9114f3');
        });
        Schema::create('tl_page_page__photos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_page_page', 'id', 'fk_e7b50a093d3c33dfa05e8ad2')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_61e3d69f6c335b11b86d');
            $table->index('account_id', 'ix_8e834920ced44ce78921fbad');
        });
        Schema::create('tl_page_page__documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_page_page', 'id', 'fk_266bae176a9c66ef2b763148')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_497017e5a2dc02aa822f');
            $table->index('account_id', 'ix_b67564d39ee2ceb35a006b03');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_page_page__documents');
        Schema::dropIfExists('tl_page_page__photos');
        Schema::dropIfExists('tl_page_page__blocks');
        Schema::dropIfExists('tl_page_page');
    }
};
