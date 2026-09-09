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
        Schema::create('tl_message_views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b3d56475f936f4c6fb2bb803');
            $table->index('account_id', 'ix_8cb5fa76c5a92d631e1d3579');
        });
        Schema::create('tl_message_views_message_views', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_views')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('views')->nullable();
            $table->integer('forwards')->nullable();
            $table->uuid('replies')->nullable();
            $table->index('replies', 'ix_ec030d8e1e34c963fadcdd85');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1f069e659d3a23a83fdeff11');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_views_message_views');
        Schema::dropIfExists('tl_message_views');
    }
};
