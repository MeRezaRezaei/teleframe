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
        Schema::create('tl_messages_translated_text_translate_result', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7d9e6df0023c95082dd0a1cc');
            $table->index('account_id', 'ix_a577888aa4e0c83887ceb279');
        });
        Schema::create('tl_messages_translated_text_translate_result__result', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_translated_text_translate_result', 'id', 'fk_4f1e46ac99c8530c54b5161d')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c8ed709320a9e3a4b3ef');
            $table->index('account_id', 'ix_89775c6ccaba07fc127f5bfe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_translated_text_translate_result__result');
        Schema::dropIfExists('tl_messages_translated_text_translate_result');
    }
};
