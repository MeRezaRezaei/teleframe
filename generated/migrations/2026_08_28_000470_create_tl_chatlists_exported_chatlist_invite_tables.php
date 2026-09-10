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
        Schema::create('tl_chatlists_exported_chatlist_invite_exporte_bc253d459003', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('filter')->nullable();
            $table->index('filter', 'ix_38b6d2d5502e817c7f47672f');
            $table->bigInteger('invite')->nullable();
            $table->index('invite', 'ix_143db4317b9f491238297e68');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0adc7b30e739d5d8272b40eb');
            $table->index('account_id', 'ix_973842f6bdfdfa888d324c83');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chatlists_exported_chatlist_invite_exporte_bc253d459003');
    }
};
