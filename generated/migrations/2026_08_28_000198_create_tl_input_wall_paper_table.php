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
        Schema::create('tl_input_wall_paper', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_dd5576aae5a947177eb69742');
            $table->index('account_id', 'ix_9eec5bc70fc35e49ef91297a');
        });
        Schema::create('tl_input_wall_paper_input_wall_paper', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_wall_paper')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_bcd4ad35addd73e60215a2fe');
            $table->unique(['account_id', 'tl_id'], 'ux_f5b05a60a5bfb0f02e69');
        });
        Schema::create('tl_input_wall_paper_input_wall_paper_no_file', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_wall_paper')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6073d9f4eb92c8b1c7eaffe5');
            $table->unique(['account_id', 'tl_id'], 'ux_a2395a73487c974a9b04');
        });
        Schema::create('tl_input_wall_paper_input_wall_paper_slug', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_wall_paper')->cascadeOnDelete();
            $table->text('slug');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e267caf930457d3e48130d6a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_wall_paper_input_wall_paper_slug');
        Schema::dropIfExists('tl_input_wall_paper_input_wall_paper_no_file');
        Schema::dropIfExists('tl_input_wall_paper_input_wall_paper');
        Schema::dropIfExists('tl_input_wall_paper');
    }
};
