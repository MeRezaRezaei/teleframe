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
        Schema::create('tl_help_peer_colors_peer_colors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_77334411917d32e5f8d8a3be');
            $table->index('account_id', 'ix_3f9a7948465daf7f0a0ed2fb');
        });
        Schema::create('tl_help_peer_colors_peer_colors__colors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_help_peer_colors_peer_colors', 'id', 'fk_6bf628018c675ecac75e19da')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ac5b2a6fe1908222ee3d');
            $table->index('account_id', 'ix_746870df5478b710e6371b82');
        });
        Schema::create('tl_help_peer_colors_peer_colors_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ac59783105ba777a85edcb52');
            $table->index('account_id', 'ix_95bba26bfd8ce588d20f4f25');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_peer_colors_peer_colors_not_modified');
        Schema::dropIfExists('tl_help_peer_colors_peer_colors__colors');
        Schema::dropIfExists('tl_help_peer_colors_peer_colors');
    }
};
