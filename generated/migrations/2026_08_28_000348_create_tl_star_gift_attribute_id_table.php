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
        Schema::create('tl_star_gift_attribute_id', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_eaa1a5c89a3e766850effce8');
            $table->index('account_id', 'ix_1224a83de05ee9ed75f2413b');
        });
        Schema::create('tl_star_gift_attribute_id_star_gift_attribute_id_backdrop', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_attribute_id')->cascadeOnDelete();
            $table->integer('backdrop_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0ce8c34b2ecb15a76fae3878');
        });
        Schema::create('tl_star_gift_attribute_id_star_gift_attribute_id_model', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_attribute_id')->cascadeOnDelete();
            $table->bigInteger('document_id');
            $table->index('document_id', 'ix_08bd0346a51ecd3d9999f864');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2fab6afdb573fb3fb6f6effa');
        });
        Schema::create('tl_star_gift_attribute_id_star_gift_attribute_id_pattern', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_attribute_id')->cascadeOnDelete();
            $table->bigInteger('document_id');
            $table->index('document_id', 'ix_f5d21aef90c51ce88509eb3d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f874fb6ba8885441041ee738');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_attribute_id_star_gift_attribute_id_pattern');
        Schema::dropIfExists('tl_star_gift_attribute_id_star_gift_attribute_id_model');
        Schema::dropIfExists('tl_star_gift_attribute_id_star_gift_attribute_id_backdrop');
        Schema::dropIfExists('tl_star_gift_attribute_id');
    }
};
