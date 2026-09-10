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
        Schema::create('tl_reaction_notifications_from_reaction_notif_70e6503a48b0', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0e4259eb0bbe8f0acc3276b1');
            $table->index('account_id', 'ix_47eda658ae439dba5329caab');
        });
        Schema::create('tl_reaction_notifications_from_reaction_notif_d9fdd2611884', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7ed56ab5641aba9d0becfdb5');
            $table->index('account_id', 'ix_437953ed7d5a4a61dad51267');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_reaction_notifications_from_reaction_notif_d9fdd2611884');
        Schema::dropIfExists('tl_reaction_notifications_from_reaction_notif_70e6503a48b0');
    }
};
