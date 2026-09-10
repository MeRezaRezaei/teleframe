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
        Schema::create('tl_users_users_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4b7289a4345d306bdfb25fe3');
            $table->index('account_id', 'ix_b7b81cb514121c9c489781b6');
        });
        Schema::create('tl_users_users_users__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_users_users_users', 'id', 'fk_21a76be64aaf11767110e09c')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9ab1062583b9b61990f1');
            $table->index('account_id', 'ix_447a2a85655dd1cc3153c27e');
        });
        Schema::create('tl_users_users_users_slice', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_92849f36b8d5afa1251042da');
            $table->index('account_id', 'ix_a7580e548c68298d34e52040');
        });
        Schema::create('tl_users_users_users_slice__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_users_users_users_slice', 'id', 'fk_aeccda9c7e4839057499a6d6')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f2d914dcd8bf411210fd');
            $table->index('account_id', 'ix_6bbc0b12dd0597d08f4e9a7f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_users_users_users_slice__users');
        Schema::dropIfExists('tl_users_users_users_slice');
        Schema::dropIfExists('tl_users_users_users__users');
        Schema::dropIfExists('tl_users_users_users');
    }
};
