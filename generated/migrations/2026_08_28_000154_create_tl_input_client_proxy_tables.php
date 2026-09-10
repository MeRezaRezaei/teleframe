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
        Schema::create('tl_input_client_proxy_input_client_proxy', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('address')->nullable();
            $table->integer('port')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_709e2984c4f29daee3d93d5e');
            $table->index('account_id', 'ix_5a25ecbe574eac5d473026a5');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_client_proxy_input_client_proxy');
    }
};
