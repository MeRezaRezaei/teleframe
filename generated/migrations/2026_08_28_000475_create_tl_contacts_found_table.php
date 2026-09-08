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
        Schema::create('tl_contacts_found', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_contacts_found_found', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_contacts_found')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_contacts_found_found__my_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_found_found')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_1c2c9da56c7f570f2be7');
        });
        Schema::create('tl_contacts_found_found__results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_found_found')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_b72f593495bdd4866ae9');
        });
        Schema::create('tl_contacts_found_found__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_found_found')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_ebc1c668fe3334c34743');
        });
        Schema::create('tl_contacts_found_found__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_found_found')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_e93204c2d5dfd2291af5');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_contacts_found_found__users');
        Schema::dropIfExists('tl_contacts_found_found__chats');
        Schema::dropIfExists('tl_contacts_found_found__results');
        Schema::dropIfExists('tl_contacts_found_found__my_results');
        Schema::dropIfExists('tl_contacts_found_found');
        Schema::dropIfExists('tl_contacts_found');
    }
};
