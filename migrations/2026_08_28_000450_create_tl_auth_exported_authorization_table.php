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
        Schema::create('tl_auth_exported_authorization', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_325e6bdd4f5aad9d3a218545');
            $table->index('account_id', 'ix_f594a251c843cdd940fc603b');
        });
        Schema::create('tl_auth_exported_authorization_exported_authorization', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_exported_authorization')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->binary('bytes');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fab8e542d1f6040d73431f9c');
            $table->unique(['account_id', 'tl_id'], 'ux_df140cad618160bf5967');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auth_exported_authorization_exported_authorization');
        Schema::dropIfExists('tl_auth_exported_authorization');
    }
};
