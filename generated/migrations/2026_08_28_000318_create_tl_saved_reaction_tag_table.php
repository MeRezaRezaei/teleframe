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
        Schema::create('tl_saved_reaction_tag', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b3eb8265473741eda48c4c05');
            $table->index('account_id', 'ix_d050d78f4b6288861ccdba63');
        });
        Schema::create('tl_saved_reaction_tag_saved_reaction_tag', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_saved_reaction_tag')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('reaction');
            $table->index('reaction', 'ix_e769706f7c51f91aa7cfa41d');
            $table->text('title')->nullable();
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_39ede5601757b7dba9973c07');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_saved_reaction_tag_saved_reaction_tag');
        Schema::dropIfExists('tl_saved_reaction_tag');
    }
};
