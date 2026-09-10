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
        Schema::create('tl_help_peer_color_set_peer_color_profile_set', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e83ba51940620912f96dea92');
            $table->index('account_id', 'ix_8d6769f8509ebc7faeebb5fe');
        });
        Schema::create('tl_help_peer_color_set_peer_color_profile_set_fb247985ae1f', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_help_peer_color_set_peer_color_profile_set', 'id', 'fk_f4fc2354bb6cd98ca038f744')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f77324d389530c50b907');
            $table->index('account_id', 'ix_e8483cbf60e052093544a5e6');
        });
        Schema::create('tl_help_peer_color_set_peer_color_profile_set__bg_colors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_help_peer_color_set_peer_color_profile_set', 'id', 'fk_f14516c0b28a7c85559e9fa0')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9d678e4d8b91e056f239');
            $table->index('account_id', 'ix_2bea9b49d066c39b49607e62');
        });
        Schema::create('tl_help_peer_color_set_peer_color_profile_set_d09cf1f8f0b4', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_help_peer_color_set_peer_color_profile_set', 'id', 'fk_2b14b41fa0dba8a63db042f4')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8199fc405b7c1eb4dee8');
            $table->index('account_id', 'ix_22de34d2237990db371f44f6');
        });
        Schema::create('tl_help_peer_color_set_peer_color_set', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_60b1eea6d6fd26ba3712339f');
            $table->index('account_id', 'ix_634612a1ec75f8ace5559ba7');
        });
        Schema::create('tl_help_peer_color_set_peer_color_set__colors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_help_peer_color_set_peer_color_set', 'id', 'fk_44e448e76abfa2762887d299')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_60878cbab65c0f3904f2');
            $table->index('account_id', 'ix_5e6992825e554b73a90e4823');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_peer_color_set_peer_color_set__colors');
        Schema::dropIfExists('tl_help_peer_color_set_peer_color_set');
        Schema::dropIfExists('tl_help_peer_color_set_peer_color_profile_set_d09cf1f8f0b4');
        Schema::dropIfExists('tl_help_peer_color_set_peer_color_profile_set__bg_colors');
        Schema::dropIfExists('tl_help_peer_color_set_peer_color_profile_set_fb247985ae1f');
        Schema::dropIfExists('tl_help_peer_color_set_peer_color_profile_set');
    }
};
