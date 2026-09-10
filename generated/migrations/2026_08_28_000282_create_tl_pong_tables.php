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
        Schema::create('tl_pong_pong', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('msg_id')->nullable();
            $table->index('msg_id', 'ix_afa690829e523801c2953a67');
            $table->bigInteger('ping_id')->nullable();
            $table->index('ping_id', 'ix_8fee571954b7f592b0430b68');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b13146c566f54eb86648dfef');
            $table->index('account_id', 'ix_b8588bdcb4e0032f5d277601');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_pong_pong');
    }
};
