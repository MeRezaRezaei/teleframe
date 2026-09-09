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
        Schema::create('tl_smsjobs_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b36b0a5aa2675090d2501984');
            $table->index('account_id', 'ix_54f77d2273b4487f7ab65b53');
        });
        Schema::create('tl_smsjobs_status_status', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_smsjobs_status')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('allow_international')->default(false);
            $table->integer('recent_sent');
            $table->integer('recent_since');
            $table->integer('recent_remains');
            $table->integer('total_sent');
            $table->integer('total_since');
            $table->text('last_gift_slug')->nullable();
            $table->text('terms_url');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5af4404517387567494cffc3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_smsjobs_status_status');
        Schema::dropIfExists('tl_smsjobs_status');
    }
};
