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
        Schema::create('tl_contacts_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_ae1ca3bf2f2a5dae82e202a4');
            $table->index('account_id', 'ix_0522377db21582cbbf57dd1e');
        });
        Schema::create('tl_contacts_contacts_contacts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_contacts_contacts')->cascadeOnDelete();
            $table->integer('saved_count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fc4948e15e187a0af0b6ef93');
        });
        Schema::create('tl_contacts_contacts_contacts__contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_contacts_contacts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5f0575acfb65453ded3f');
            $table->index('account_id', 'ix_88909f3f54f6e1bab0016283');
        });
        Schema::create('tl_contacts_contacts_contacts__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_contacts_contacts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_57d9da3e3210581e41de');
            $table->index('account_id', 'ix_406c7992afb15dd1aea8b56e');
        });
        Schema::create('tl_contacts_contacts_contacts_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_contacts_contacts')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_53e44e9039c3ee009b4841a8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_contacts_contacts_contacts_not_modified');
        Schema::dropIfExists('tl_contacts_contacts_contacts__users');
        Schema::dropIfExists('tl_contacts_contacts_contacts__contacts');
        Schema::dropIfExists('tl_contacts_contacts_contacts');
        Schema::dropIfExists('tl_contacts_contacts');
    }
};
