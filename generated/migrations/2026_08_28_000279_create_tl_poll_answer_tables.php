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
        Schema::create('tl_poll_answer_input_poll_answer', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_cdb2338d9be0cb0c6bc8abf7');
            $table->bigInteger('media')->nullable();
            $table->index('media', 'ix_92139985ab166088e2ef17d0');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c9f1717a608ae0208aee1395');
            $table->index('account_id', 'ix_c463dc9beed4d96e0d24496c');
        });
        Schema::create('tl_poll_answer_poll_answer', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_0d1901301be18f714889b3fd');
            $table->binary('option')->nullable();
            $table->bigInteger('media')->nullable();
            $table->index('media', 'ix_5d75cad2e72622c1c77336a4');
            $table->bigInteger('added_by')->nullable();
            $table->index('added_by', 'ix_17ae36b17b3c1bb0fe4db3d7');
            $table->integer('date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8bf472e74f51dccb8881313c');
            $table->index('account_id', 'ix_70411473333a267a27aaaa49');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_poll_answer_poll_answer');
        Schema::dropIfExists('tl_poll_answer_input_poll_answer');
    }
};
