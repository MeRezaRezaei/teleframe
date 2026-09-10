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
        Schema::create('tl_folder_folder', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('autofill_new_broadcasts')->default(false);
            $table->boolean('autofill_public_groups')->default(false);
            $table->boolean('autofill_new_correspondents')->default(false);
            $table->integer('tl_id')->nullable();
            $table->text('title')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_51c5fcaffeaeee308d13a9e3');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7ec2dc1c700446f281295e9a');
            $table->index('account_id', 'ix_84620fc0b5021cde5f1e2dfc');
            $table->unique(['account_id'], 'ux_105ecc6344e8b65ce373');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_folder_folder');
    }
};
