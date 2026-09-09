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
        Schema::create('tl_input_theme', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_bf101900cb52dcf1eb767855');
            $table->index('account_id', 'ix_3c4bd1652e32ec08e9b735f5');
        });
        Schema::create('tl_input_theme_input_theme', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_theme')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_006f6497023a93cca3893eb1');
            $table->unique(['account_id', 'tl_id'], 'ux_ec4c6fc1a791b91e8f0d');
        });
        Schema::create('tl_input_theme_input_theme_slug', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_theme')->cascadeOnDelete();
            $table->text('slug');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2fb7da8223faf0f82de31068');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_theme_input_theme_slug');
        Schema::dropIfExists('tl_input_theme_input_theme');
        Schema::dropIfExists('tl_input_theme');
    }
};
