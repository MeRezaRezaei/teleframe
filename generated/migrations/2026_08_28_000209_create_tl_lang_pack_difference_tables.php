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
        Schema::create('tl_lang_pack_difference_lang_pack_difference', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('lang_code')->nullable();
            $table->integer('from_version')->nullable();
            $table->integer('version')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a77cad810cab6cb38831b54d');
            $table->index('account_id', 'ix_f430674713de1c5f9ef435e9');
        });
        Schema::create('tl_lang_pack_difference_lang_pack_difference__strings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_lang_pack_difference_lang_pack_difference', 'id', 'fk_356e8a84b1ad2f6f016be472')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b0d6c7f5f39fcd4769d4');
            $table->index('account_id', 'ix_57ad79242b3ababc5b0c3618');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_lang_pack_difference_lang_pack_difference__strings');
        Schema::dropIfExists('tl_lang_pack_difference_lang_pack_difference');
    }
};
