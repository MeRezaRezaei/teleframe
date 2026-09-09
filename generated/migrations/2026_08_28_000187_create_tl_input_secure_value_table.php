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
        Schema::create('tl_input_secure_value', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_bd29210234f66ec48e39f955');
            $table->index('account_id', 'ix_7357d18e0c54cba76b937ea4');
        });
        Schema::create('tl_input_secure_value_input_secure_value', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_secure_value')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('tl_type');
            $table->index('tl_type', 'ix_993f19e404eaa70bdfcbfdd0');
            $table->uuid('data')->nullable();
            $table->index('data', 'ix_4916ca0b54f0d3c8874faa15');
            $table->uuid('front_side')->nullable();
            $table->index('front_side', 'ix_4799d4d5f49e564f8b853df0');
            $table->uuid('reverse_side')->nullable();
            $table->index('reverse_side', 'ix_30d9a645b9b6e685609cdf97');
            $table->uuid('selfie')->nullable();
            $table->index('selfie', 'ix_7e5086dd5a971e035fe803f2');
            $table->uuid('plain_data')->nullable();
            $table->index('plain_data', 'ix_b03e1fba135a07113ae056c5');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_295b08806d51a816fb24e1cb');
        });
        Schema::create('tl_input_secure_value_input_secure_value__translation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_secure_value_input_secure_value')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_321b474c8b32bb43cc55');
            $table->index('account_id', 'ix_18b229e80394a988f57f4898');
        });
        Schema::create('tl_input_secure_value_input_secure_value__files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_secure_value_input_secure_value')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ac030c7049dc518317da');
            $table->index('account_id', 'ix_22ab0c5ef3d7bae6e512ad7c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_secure_value_input_secure_value__files');
        Schema::dropIfExists('tl_input_secure_value_input_secure_value__translation');
        Schema::dropIfExists('tl_input_secure_value_input_secure_value');
        Schema::dropIfExists('tl_input_secure_value');
    }
};
