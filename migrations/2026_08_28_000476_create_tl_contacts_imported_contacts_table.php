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
        Schema::create('tl_contacts_imported_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_contacts_imported_contacts_imported_contacts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_contacts_imported_contacts')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_contacts_imported_contacts_imported_contacts__imported', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_imported_contacts_imported_contacts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_9a72d6b9667af2c4d9eb');
        });
        Schema::create('tl_contacts_imported_contacts_imported_contac_05412ff68ebc', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_imported_contacts_imported_contacts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_2507964dabd9044aeee0');
        });
        Schema::create('tl_contacts_imported_contacts_imported_contac_7e82948d3852', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_imported_contacts_imported_contacts')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_7e926d326e0690eba859');
        });
        Schema::create('tl_contacts_imported_contacts_imported_contacts__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_imported_contacts_imported_contacts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_d9cf3f763c145e887d16');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_contacts_imported_contacts_imported_contacts__users');
        Schema::dropIfExists('tl_contacts_imported_contacts_imported_contac_7e82948d3852');
        Schema::dropIfExists('tl_contacts_imported_contacts_imported_contac_05412ff68ebc');
        Schema::dropIfExists('tl_contacts_imported_contacts_imported_contacts__imported');
        Schema::dropIfExists('tl_contacts_imported_contacts_imported_contacts');
        Schema::dropIfExists('tl_contacts_imported_contacts');
    }
};
