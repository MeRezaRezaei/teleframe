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
        Schema::create('tl_secure_required_type', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_secure_required_type_secure_required_type', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_required_type')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('native_names')->default(false);
            $table->boolean('selfie_required')->default(false);
            $table->boolean('translation_required')->default(false);
            $table->uuid('tl_type');
            $table->timestamps();
        });
        Schema::create('tl_secure_required_type_secure_required_type_one_of', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_required_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_secure_required_type_secure_required_type_one_of__types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_secure_required_type_secure_required_type_one_of')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_ea3cb4d8b818b85184c2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_secure_required_type_secure_required_type_one_of__types');
        Schema::dropIfExists('tl_secure_required_type_secure_required_type_one_of');
        Schema::dropIfExists('tl_secure_required_type_secure_required_type');
        Schema::dropIfExists('tl_secure_required_type');
    }
};
