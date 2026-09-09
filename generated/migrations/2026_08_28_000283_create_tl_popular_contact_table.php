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
        Schema::create('tl_popular_contact', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_4154b08c008383faeebb6c00');
            $table->index('account_id', 'ix_7b922e35b6913a1bd55234dc');
        });
        Schema::create('tl_popular_contact_popular_contact', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_popular_contact')->cascadeOnDelete();
            $table->bigInteger('client_id');
            $table->index('client_id', 'ix_d8d727d71f36a415f64ed421');
            $table->integer('importers');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c5570944d6675e876a752b78');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_popular_contact_popular_contact');
        Schema::dropIfExists('tl_popular_contact');
    }
};
