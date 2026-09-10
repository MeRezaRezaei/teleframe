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
        Schema::create('tl_messages_archived_stickers_archived_stickers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_88e585338589a7c1f1830bdd');
            $table->index('account_id', 'ix_1e055c3c4fbd0ddddc857575');
        });
        Schema::create('tl_messages_archived_stickers_archived_stickers__sets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_archived_stickers_archived_stickers', 'id', 'fk_de45cd821a766107e46e4383')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_694be764ca12c02a247d');
            $table->index('account_id', 'ix_14042b9a2b348f650de86462');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_archived_stickers_archived_stickers__sets');
        Schema::dropIfExists('tl_messages_archived_stickers_archived_stickers');
    }
};
