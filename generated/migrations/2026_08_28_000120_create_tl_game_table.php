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
        Schema::create('tl_game', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_092de3b5b46d03db137f9f1e');
            $table->index('account_id', 'ix_e36fa0bb5f65b2dd08cdbd34');
        });
        Schema::create('tl_game_game', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_game')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->text('short_name');
            $table->text('title');
            $table->text('description');
            $table->uuid('photo');
            $table->index('photo', 'ix_348d11d83f5faff82b5f8df2');
            $table->uuid('document')->nullable();
            $table->index('document', 'ix_f55086c5b76a6e8be3518f89');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6b572ca76dacba2f6fcb074a');
            $table->unique(['account_id', 'tl_id'], 'ux_a1214b2ec0ab6ca74aca');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_game_game');
        Schema::dropIfExists('tl_game');
    }
};
