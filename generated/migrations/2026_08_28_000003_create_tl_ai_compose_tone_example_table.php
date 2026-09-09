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
        Schema::create('tl_ai_compose_tone_example', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_9b7a6a47347be023ef980f88');
            $table->index('account_id', 'ix_782cb97e5a744a13fd9f8690');
        });
        Schema::create('tl_ai_compose_tone_example_ai_compose_tone_example', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_ai_compose_tone_example')->cascadeOnDelete();
            $table->uuid('tl_from');
            $table->index('tl_from', 'ix_04e2790b707c94158f93c119');
            $table->uuid('tl_to');
            $table->index('tl_to', 'ix_5863da33347623134ffc5996');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a37e8e8b21540e0710ca2d93');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_ai_compose_tone_example_ai_compose_tone_example');
        Schema::dropIfExists('tl_ai_compose_tone_example');
    }
};
