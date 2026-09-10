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
        Schema::create('tl_messages_composed_message_with_a_i_compose_55280cfdf5bd', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('result_text')->nullable();
            $table->index('result_text', 'ix_112868f3990a95285076d035');
            $table->bigInteger('diff_text')->nullable();
            $table->index('diff_text', 'ix_f9ba296d101e063e11613535');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ae5996b7503d319b1310bd6e');
            $table->index('account_id', 'ix_79ec9f17220eb0d1b50c894e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_composed_message_with_a_i_compose_55280cfdf5bd');
    }
};
