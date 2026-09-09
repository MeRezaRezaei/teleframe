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
        Schema::create('tl_chatlists_exported_chatlist_invite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_72b4affd5539c1ed72ce38b3');
            $table->index('account_id', 'ix_3f1d418a7822ae9541df517b');
        });
        Schema::create('tl_chatlists_exported_chatlist_invite_exporte_bc253d459003', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chatlists_exported_chatlist_invite')->cascadeOnDelete();
            $table->uuid('filter');
            $table->index('filter', 'ix_38b6d2d5502e817c7f47672f');
            $table->uuid('invite');
            $table->index('invite', 'ix_143db4317b9f491238297e68');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_973842f6bdfdfa888d324c83');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chatlists_exported_chatlist_invite_exporte_bc253d459003');
        Schema::dropIfExists('tl_chatlists_exported_chatlist_invite');
    }
};
