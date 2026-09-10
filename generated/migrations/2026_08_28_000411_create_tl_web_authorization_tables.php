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
        Schema::create('tl_web_authorization_web_authorization', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('bot_id')->nullable();
            $table->index('bot_id', 'ix_11377894211190674bc05091');
            $table->text('domain')->nullable();
            $table->text('browser')->nullable();
            $table->text('platform')->nullable();
            $table->integer('date_created')->nullable();
            $table->integer('date_active')->nullable();
            $table->text('ip')->nullable();
            $table->text('region')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_24cea39c8356b7d73c3117ad');
            $table->index('account_id', 'ix_6ed3aeba74fabbec7800f38a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_web_authorization_web_authorization');
    }
};
