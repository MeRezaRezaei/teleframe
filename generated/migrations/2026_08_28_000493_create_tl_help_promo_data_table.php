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
        Schema::create('tl_help_promo_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_76a9e1548f00c36e83ce97de');
            $table->index('account_id', 'ix_5acc5bec89a33bce91a5c0b3');
        });
        Schema::create('tl_help_promo_data_promo_data', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_promo_data')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('proxy')->default(false);
            $table->integer('expires');
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_8ddf39c8b1bc9574777ecaa6');
            $table->text('psa_type')->nullable();
            $table->text('psa_message')->nullable();
            $table->uuid('custom_pending_suggestion')->nullable();
            $table->index('custom_pending_suggestion', 'ix_65e23a0baf74323713b4df84');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b4da1e1ed7aef87bcdd87f30');
        });
        Schema::create('tl_help_promo_data_promo_data__pending_suggestions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_promo_data_promo_data')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_efa746198f1f88861449');
            $table->index('account_id', 'ix_72842baef7f4e1202876bd57');
        });
        Schema::create('tl_help_promo_data_promo_data__dismissed_suggestions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_promo_data_promo_data')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_833f5cba9c46ecaf206b');
            $table->index('account_id', 'ix_a6b1d619d3234d1aed180c29');
        });
        Schema::create('tl_help_promo_data_promo_data__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_promo_data_promo_data')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_df9e0a679fd3409d1682');
            $table->index('account_id', 'ix_0c5ecf719de066b06b9061f3');
        });
        Schema::create('tl_help_promo_data_promo_data__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_promo_data_promo_data')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_2d3fbca0e737e13eed68');
            $table->index('account_id', 'ix_cc94544685f3117cf2dd8805');
        });
        Schema::create('tl_help_promo_data_promo_data_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_promo_data')->cascadeOnDelete();
            $table->integer('expires');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1687363d7bd607bd8f4c29e1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_promo_data_promo_data_empty');
        Schema::dropIfExists('tl_help_promo_data_promo_data__users');
        Schema::dropIfExists('tl_help_promo_data_promo_data__chats');
        Schema::dropIfExists('tl_help_promo_data_promo_data__dismissed_suggestions');
        Schema::dropIfExists('tl_help_promo_data_promo_data__pending_suggestions');
        Schema::dropIfExists('tl_help_promo_data_promo_data');
        Schema::dropIfExists('tl_help_promo_data');
    }
};
