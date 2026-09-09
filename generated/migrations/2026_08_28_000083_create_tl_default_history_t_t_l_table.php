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
        Schema::create('tl_default_history_t_t_l', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_43a8c9608ee4e12dc92c7d6a');
            $table->index('account_id', 'ix_97630d1138230729fb761d98');
        });
        Schema::create('tl_default_history_t_t_l_default_history_t_t_l', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_default_history_t_t_l')->cascadeOnDelete();
            $table->integer('period');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7791792ec0589b9f41f23fef');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_default_history_t_t_l_default_history_t_t_l');
        Schema::dropIfExists('tl_default_history_t_t_l');
    }
};
