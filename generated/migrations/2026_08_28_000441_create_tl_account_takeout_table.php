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
        Schema::create('tl_account_takeout', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_24d28892cba809a7d389af81');
            $table->index('account_id', 'ix_6baa31f84557c15355104a58');
        });
        Schema::create('tl_account_takeout_takeout', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_takeout')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2edd155dbe6299e03df1fa6d');
            $table->unique(['account_id', 'tl_id'], 'ux_c8826fe2f6a8e1b64407');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_takeout_takeout');
        Schema::dropIfExists('tl_account_takeout');
    }
};
