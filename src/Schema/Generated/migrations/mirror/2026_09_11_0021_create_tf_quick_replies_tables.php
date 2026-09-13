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
        Schema::create('tf_quick_replies', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->integer('shortcut_id')->unsigned();
        $table->text('shortcut');
        $table->integer('top_message')->unsigned();
        $table->integer('count')->unsigned();
        $table->primary(['account_id', 'shortcut_id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_quick_replies');

        Schema::dropIfExists('tf_quick_replies');

    }
};
