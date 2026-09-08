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
        Schema::create('tl_reaction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_reaction_reaction_custom_emoji', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_reaction')->cascadeOnDelete();
            $table->bigInteger('document_id');
            $table->timestamps();
        });
        Schema::create('tl_reaction_reaction_emoji', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_reaction')->cascadeOnDelete();
            $table->text('emoticon');
            $table->timestamps();
        });
        Schema::create('tl_reaction_reaction_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_reaction')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_reaction_reaction_paid', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_reaction')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_reaction_reaction_paid');
        Schema::dropIfExists('tl_reaction_reaction_empty');
        Schema::dropIfExists('tl_reaction_reaction_emoji');
        Schema::dropIfExists('tl_reaction_reaction_custom_emoji');
        Schema::dropIfExists('tl_reaction');
    }
};
