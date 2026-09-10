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
        Schema::create('tl_account_chat_themes_chat_themes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('hash')->nullable();
            $table->text('next_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a7eabea96393309766503111');
            $table->index('account_id', 'ix_c7d6717409039f4cd504e9fc');
        });
        Schema::create('tl_account_chat_themes_chat_themes__themes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_account_chat_themes_chat_themes', 'id', 'fk_cdf66605c0c47ace8ee96416')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_db95a5e52d2f4cfa98d1');
            $table->index('account_id', 'ix_9f76462b71af5d6fb76f8cb4');
        });
        Schema::create('tl_account_chat_themes_chat_themes__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_account_chat_themes_chat_themes', 'id', 'fk_5d92afae05b8842872462e6d')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6364c9624de1ee497135');
            $table->index('account_id', 'ix_b6cddc3b87c05d1526a5d761');
        });
        Schema::create('tl_account_chat_themes_chat_themes__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_account_chat_themes_chat_themes', 'id', 'fk_dc70390d7cb88238a4f3af2c')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_600632584b25becef455');
            $table->index('account_id', 'ix_2ab2f9f62c21cf000c12e3c7');
        });
        Schema::create('tl_account_chat_themes_chat_themes_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_80b4b840faa4bfd34acff840');
            $table->index('account_id', 'ix_865aec022418747abfa84c53');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_chat_themes_chat_themes_not_modified');
        Schema::dropIfExists('tl_account_chat_themes_chat_themes__users');
        Schema::dropIfExists('tl_account_chat_themes_chat_themes__chats');
        Schema::dropIfExists('tl_account_chat_themes_chat_themes__themes');
        Schema::dropIfExists('tl_account_chat_themes_chat_themes');
    }
};
