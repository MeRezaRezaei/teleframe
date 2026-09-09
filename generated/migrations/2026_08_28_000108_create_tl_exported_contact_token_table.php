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
        Schema::create('tl_exported_contact_token', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_7a8d9fcf6db667caefd1a2a7');
            $table->index('account_id', 'ix_793330deb49bce73b6f0f360');
        });
        Schema::create('tl_exported_contact_token_exported_contact_token', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_exported_contact_token')->cascadeOnDelete();
            $table->text('url');
            $table->integer('expires');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3128b40bbba33d2e96ada189');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_exported_contact_token_exported_contact_token');
        Schema::dropIfExists('tl_exported_contact_token');
    }
};
