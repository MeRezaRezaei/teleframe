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
        Schema::create('tl_smsjobs_status_status', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('allow_international')->default(false);
            $table->integer('recent_sent')->nullable();
            $table->integer('recent_since')->nullable();
            $table->integer('recent_remains')->nullable();
            $table->integer('total_sent')->nullable();
            $table->integer('total_since')->nullable();
            $table->text('last_gift_slug')->nullable();
            $table->text('terms_url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ea6c3f5e10ddf44f583c89c4');
            $table->index('account_id', 'ix_5af4404517387567494cffc3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_smsjobs_status_status');
    }
};
