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
        Schema::create('tl_help_deep_link_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_73c296f766ff2205bead264d');
            $table->index('account_id', 'ix_0cf812cb0ae5d7b78072c226');
        });
        Schema::create('tl_help_deep_link_info_deep_link_info', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_deep_link_info')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('update_app')->default(false);
            $table->text('message');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_43f58bb00af4a4801b193ae5');
        });
        Schema::create('tl_help_deep_link_info_deep_link_info__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_deep_link_info_deep_link_info')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8fc99dc4c656d4eb5fbf');
            $table->index('account_id', 'ix_1fb823ccc8b197469b7b689d');
        });
        Schema::create('tl_help_deep_link_info_deep_link_info_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_deep_link_info')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7c2db6b8e24dacfa52dfb552');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_deep_link_info_deep_link_info_empty');
        Schema::dropIfExists('tl_help_deep_link_info_deep_link_info__entities');
        Schema::dropIfExists('tl_help_deep_link_info_deep_link_info');
        Schema::dropIfExists('tl_help_deep_link_info');
    }
};
