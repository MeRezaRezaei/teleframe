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
        Schema::create('tl_phone_group_call_stars_group_call_stars', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('total_stars')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8a7e46497eff50d9f1242fdd');
            $table->index('account_id', 'ix_8e14421b42da9658e2b1510d');
        });
        Schema::create('tl_phone_group_call_stars_group_call_stars__top_donors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_phone_group_call_stars_group_call_stars', 'id', 'fk_b02b1a74165ae2a8bb66151e')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4a665a89042cfc2c1fc3');
            $table->index('account_id', 'ix_4a65b500c949e6a2a3690d48');
        });
        Schema::create('tl_phone_group_call_stars_group_call_stars__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_phone_group_call_stars_group_call_stars', 'id', 'fk_47647eb8466d7107b0304589')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f8ba20ba8942fc8c645b');
            $table->index('account_id', 'ix_a0ca90a59ffe2ed056aee5e2');
        });
        Schema::create('tl_phone_group_call_stars_group_call_stars__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_phone_group_call_stars_group_call_stars', 'id', 'fk_8d13719a7ee04ac9310dde94')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f1b7db4ab6677cce679f');
            $table->index('account_id', 'ix_043aae2e4a23b0025183485d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_group_call_stars_group_call_stars__users');
        Schema::dropIfExists('tl_phone_group_call_stars_group_call_stars__chats');
        Schema::dropIfExists('tl_phone_group_call_stars_group_call_stars__top_donors');
        Schema::dropIfExists('tl_phone_group_call_stars_group_call_stars');
    }
};
