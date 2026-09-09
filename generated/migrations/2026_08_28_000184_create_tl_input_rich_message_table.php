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
        Schema::create('tl_input_rich_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_20fcf44ec7f10e357938928b');
            $table->index('account_id', 'ix_cee3372d17de10d371f33adc');
        });
        Schema::create('tl_input_rich_message_input_rich_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_rich_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('rtl')->default(false);
            $table->boolean('noautolink')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9335d158f829d23858eeb21b');
        });
        Schema::create('tl_input_rich_message_input_rich_message__blocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_rich_message_input_rich_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a89f0f84fe7ad2b9e7e1');
            $table->index('account_id', 'ix_140362d868f55e78e2ecc111');
        });
        Schema::create('tl_input_rich_message_input_rich_message__photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_rich_message_input_rich_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0d9db4659ed599622647');
            $table->index('account_id', 'ix_1564ba39819271752515df09');
        });
        Schema::create('tl_input_rich_message_input_rich_message__documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_rich_message_input_rich_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_19dc59d4df584d7dc014');
            $table->index('account_id', 'ix_676aa895298a615e2a647ac5');
        });
        Schema::create('tl_input_rich_message_input_rich_message__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_rich_message_input_rich_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ed9aedd0907a5a914ade');
            $table->index('account_id', 'ix_7f2665eec04dd00edda0bb00');
        });
        Schema::create('tl_input_rich_message_input_rich_message_h_t_m_l', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_rich_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('rtl')->default(false);
            $table->boolean('noautolink')->default(false);
            $table->text('html');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_555017c8718521fb5e6bfdf1');
        });
        Schema::create('tl_input_rich_message_input_rich_message_h_t_m_l__files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_rich_message_input_rich_message_h_t_m_l')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_eed8bebb683c6f5d6eaa');
            $table->index('account_id', 'ix_664d117d5c418bc767d906d8');
        });
        Schema::create('tl_input_rich_message_input_rich_message_markdown', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_rich_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('rtl')->default(false);
            $table->boolean('noautolink')->default(false);
            $table->text('markdown');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5f73a332f5e4cb192814c696');
        });
        Schema::create('tl_input_rich_message_input_rich_message_markdown__files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_rich_message_input_rich_message_markdown')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_48dadf92d28948fa0b0c');
            $table->index('account_id', 'ix_2477590c551957e44b753566');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_rich_message_input_rich_message_markdown__files');
        Schema::dropIfExists('tl_input_rich_message_input_rich_message_markdown');
        Schema::dropIfExists('tl_input_rich_message_input_rich_message_h_t_m_l__files');
        Schema::dropIfExists('tl_input_rich_message_input_rich_message_h_t_m_l');
        Schema::dropIfExists('tl_input_rich_message_input_rich_message__users');
        Schema::dropIfExists('tl_input_rich_message_input_rich_message__documents');
        Schema::dropIfExists('tl_input_rich_message_input_rich_message__photos');
        Schema::dropIfExists('tl_input_rich_message_input_rich_message__blocks');
        Schema::dropIfExists('tl_input_rich_message_input_rich_message');
        Schema::dropIfExists('tl_input_rich_message');
    }
};
