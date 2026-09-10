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
        Schema::create('tl_message_container_msg_container', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6ce8b0caad16afb33319cc81');
            $table->index('account_id', 'ix_1a9608954e2986845831239e');
        });
        Schema::create('tl_message_container_msg_container__messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_7f840e33defcc5304f993c1d')->references('id')->on('tl_message_container_msg_container')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6d747b95a3609e4ac7aa');
            $table->index('account_id', 'ix_848539fda47c367f28aeb447');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_container_msg_container__messages');
        Schema::dropIfExists('tl_message_container_msg_container');
    }
};
