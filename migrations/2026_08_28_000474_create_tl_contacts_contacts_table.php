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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_contacts_contacts_contacts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_contacts_contacts')->cascadeOnDelete();
            $table->integer('saved_count');
            $table->timestamps();
        });
        Schema::create('tl_contacts_contacts_contacts__contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_contacts_contacts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_5f0575acfb65453ded3f');
        });
        Schema::create('tl_contacts_contacts_contacts__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_contacts_contacts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_57d9da3e3210581e41de');
        });
        Schema::create('tl_contacts_contacts_contacts_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_contacts_contacts')->cascadeOnDelete();
            $table->timestamps();
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
