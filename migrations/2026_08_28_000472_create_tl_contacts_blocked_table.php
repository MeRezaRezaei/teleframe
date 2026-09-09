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
        Schema::create('tl_contacts_blocked', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_09c23a35c8a1be5d5e0daac4');
            $table->index('account_id', 'ix_caf06958f419e7996c29bdbe');
        });
        Schema::create('tl_contacts_blocked_blocked', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_contacts_blocked')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3e48e2b02017522deb2c1243');
        });
        Schema::create('tl_contacts_blocked_blocked__blocked', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_blocked_blocked')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e2560b0c49f2d128f6b3');
            $table->index('account_id', 'ix_fb3db514a8742da8cd456bf5');
        });
        Schema::create('tl_contacts_blocked_blocked__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_blocked_blocked')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_dc6635d52538a35e7d98');
            $table->index('account_id', 'ix_58ca29339677acadab486f73');
        });
        Schema::create('tl_contacts_blocked_blocked__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_blocked_blocked')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0438b01d3bcd7d651ab3');
            $table->index('account_id', 'ix_128a43de462796909fdabf86');
        });
        Schema::create('tl_contacts_blocked_blocked_slice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_contacts_blocked')->cascadeOnDelete();
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6f273dcf4949d7d914369b9b');
        });
        Schema::create('tl_contacts_blocked_blocked_slice__blocked', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_blocked_blocked_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9550f9de7a5338268a29');
            $table->index('account_id', 'ix_eb8d8e4dbadd519ecf114ad6');
        });
        Schema::create('tl_contacts_blocked_blocked_slice__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_blocked_blocked_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6481fc0715e79f9e6ff4');
            $table->index('account_id', 'ix_eeb7b49b0403bcadc9f627cb');
        });
        Schema::create('tl_contacts_blocked_blocked_slice__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_blocked_blocked_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_58d43b225e6ee18fe874');
            $table->index('account_id', 'ix_14573a6eae1c324db4c716aa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_contacts_blocked_blocked_slice__users');
        Schema::dropIfExists('tl_contacts_blocked_blocked_slice__chats');
        Schema::dropIfExists('tl_contacts_blocked_blocked_slice__blocked');
        Schema::dropIfExists('tl_contacts_blocked_blocked_slice');
        Schema::dropIfExists('tl_contacts_blocked_blocked__users');
        Schema::dropIfExists('tl_contacts_blocked_blocked__chats');
        Schema::dropIfExists('tl_contacts_blocked_blocked__blocked');
        Schema::dropIfExists('tl_contacts_blocked_blocked');
        Schema::dropIfExists('tl_contacts_blocked');
    }
};
