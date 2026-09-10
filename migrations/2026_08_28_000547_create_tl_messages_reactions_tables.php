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
        Schema::create('tl_messages_reactions_reactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_dda1cffae7f28d24515f1303');
            $table->index('account_id', 'ix_de64e48335f778e049d68b80');
        });
        Schema::create('tl_messages_reactions_reactions__reactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_reactions_reactions', 'id', 'fk_ec0c3bd97219eb14d643e496')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_340cb4dfc7bf05d30c92');
            $table->index('account_id', 'ix_3856b479e7249559b9718984');
        });
        Schema::create('tl_messages_reactions_reactions_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_72bc712c1056c2c98402c7fc');
            $table->index('account_id', 'ix_133ea8be634c99eebdc23042');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_reactions_reactions_not_modified');
        Schema::dropIfExists('tl_messages_reactions_reactions__reactions');
        Schema::dropIfExists('tl_messages_reactions_reactions');
    }
};
