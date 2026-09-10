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
        Schema::create('tl_account_themes_themes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7a8996a302134cb4f3feda54');
            $table->index('account_id', 'ix_b194df13eb2e94e6dacee4ef');
        });
        Schema::create('tl_account_themes_themes__themes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_38cbf84cba991b3f6604256d')->references('id')->on('tl_account_themes_themes')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_043ed26f3f28110b8bf3');
            $table->index('account_id', 'ix_0e44780e2a8877f1882554c4');
        });
        Schema::create('tl_account_themes_themes_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_df34157256ac7331754aba7d');
            $table->index('account_id', 'ix_58aaf8e33499e7a15b9f8548');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_themes_themes_not_modified');
        Schema::dropIfExists('tl_account_themes_themes__themes');
        Schema::dropIfExists('tl_account_themes_themes');
    }
};
