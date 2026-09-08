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
        Schema::create('tl_message_fwd_header', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_message_fwd_header_message_fwd_header', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_fwd_header')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('imported')->default(false);
            $table->boolean('saved_out')->default(false);
            $table->uuid('from_id')->nullable();
            $table->text('from_name')->nullable();
            $table->integer('date');
            $table->integer('channel_post')->nullable();
            $table->text('post_author')->nullable();
            $table->uuid('saved_from_peer')->nullable();
            $table->integer('saved_from_msg_id')->nullable();
            $table->uuid('saved_from_id')->nullable();
            $table->text('saved_from_name')->nullable();
            $table->integer('saved_date')->nullable();
            $table->text('psa_type')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_fwd_header_message_fwd_header');
        Schema::dropIfExists('tl_message_fwd_header');
    }
};
