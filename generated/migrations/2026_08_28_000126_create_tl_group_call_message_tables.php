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
        Schema::create('tl_group_call_message_group_call_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('from_admin')->default(false);
            $table->integer('tl_id')->nullable();
            $table->bigInteger('from_id')->nullable();
            $table->index('from_id', 'ix_89df86a9d3e1cb53f503dcfb');
            $table->integer('date')->nullable();
            $table->bigInteger('message')->nullable();
            $table->index('message', 'ix_828cd7b674f51e94f9a5287f');
            $table->bigInteger('paid_message_stars')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7bfaedc2063881ac4238eeb2');
            $table->index('account_id', 'ix_260bf31a35cc49f9576d2d69');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_group_call_message_group_call_message');
    }
};
