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
        Schema::create('tl_rich_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_16c58dfd488dc207a22f34d2');
            $table->index('account_id', 'ix_adea1962afd170716bfe09c4');
        });
        Schema::create('tl_rich_message_rich_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('rtl')->default(false);
            $table->boolean('part')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_832f0dbeaa9d9d033455b991');
        });
        Schema::create('tl_rich_message_rich_message__blocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_rich_message_rich_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1e1786595afaf2099eac');
            $table->index('account_id', 'ix_63af5624107007399d04f090');
        });
        Schema::create('tl_rich_message_rich_message__photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_rich_message_rich_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_106233844b301effc4aa');
            $table->index('account_id', 'ix_6dc04f20b825010894dce3b8');
        });
        Schema::create('tl_rich_message_rich_message__documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_rich_message_rich_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0a4dfab3c9e17ab8f606');
            $table->index('account_id', 'ix_0879ef360903e5ddf4c39038');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_rich_message_rich_message__documents');
        Schema::dropIfExists('tl_rich_message_rich_message__photos');
        Schema::dropIfExists('tl_rich_message_rich_message__blocks');
        Schema::dropIfExists('tl_rich_message_rich_message');
        Schema::dropIfExists('tl_rich_message');
    }
};
