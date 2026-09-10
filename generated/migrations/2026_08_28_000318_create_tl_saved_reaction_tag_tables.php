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
        Schema::create('tl_saved_reaction_tag_saved_reaction_tag', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('reaction')->nullable();
            $table->index('reaction', 'ix_e769706f7c51f91aa7cfa41d');
            $table->text('title')->nullable();
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f27db24ba734eb95480f25e6');
            $table->index('account_id', 'ix_39ede5601757b7dba9973c07');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_saved_reaction_tag_saved_reaction_tag');
    }
};
