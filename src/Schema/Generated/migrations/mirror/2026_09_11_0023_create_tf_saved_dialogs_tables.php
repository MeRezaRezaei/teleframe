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
        Schema::create('tf_saved_dialogs', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->text('constructor');
        $table->integer('top_message')->unsigned();
        $table->boolean('pinned')->default(false);
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_saved_dialogs');

        Schema::dropIfExists('tf_saved_dialogs');

    }
};
