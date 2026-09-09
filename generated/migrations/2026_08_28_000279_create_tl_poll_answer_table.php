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
        Schema::create('tl_poll_answer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_49a7639853ec3eaee2821cf5');
            $table->index('account_id', 'ix_320a3b980984a59152f9a500');
        });
        Schema::create('tl_poll_answer_input_poll_answer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_poll_answer')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('text');
            $table->index('text', 'ix_cdb2338d9be0cb0c6bc8abf7');
            $table->uuid('media')->nullable();
            $table->index('media', 'ix_92139985ab166088e2ef17d0');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c463dc9beed4d96e0d24496c');
        });
        Schema::create('tl_poll_answer_poll_answer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_poll_answer')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('text');
            $table->index('text', 'ix_0d1901301be18f714889b3fd');
            $table->binary('option');
            $table->uuid('media')->nullable();
            $table->index('media', 'ix_5d75cad2e72622c1c77336a4');
            $table->bigInteger('added_by')->nullable();
            $table->index('added_by', 'ix_17ae36b17b3c1bb0fe4db3d7');
            $table->integer('date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_70411473333a267a27aaaa49');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_poll_answer_poll_answer');
        Schema::dropIfExists('tl_poll_answer_input_poll_answer');
        Schema::dropIfExists('tl_poll_answer');
    }
};
