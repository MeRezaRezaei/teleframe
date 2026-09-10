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
        Schema::create('tl_phone_join_as_peers_join_as_peers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_adf119bcb313451a56607081');
            $table->index('account_id', 'ix_c33d2d43d30488b936d898e7');
        });
        Schema::create('tl_phone_join_as_peers_join_as_peers__peers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_phone_join_as_peers_join_as_peers', 'id', 'fk_3b6f2a2369cf257124c494fd')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6c44ef7c01564ec5430b');
            $table->index('account_id', 'ix_1988e4c146fd889778b069d8');
        });
        Schema::create('tl_phone_join_as_peers_join_as_peers__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_phone_join_as_peers_join_as_peers', 'id', 'fk_c4242c3f8392ad646356431d')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_2cc3507b519d187a9330');
            $table->index('account_id', 'ix_36b11dc0f6edefd3615f047a');
        });
        Schema::create('tl_phone_join_as_peers_join_as_peers__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_phone_join_as_peers_join_as_peers', 'id', 'fk_7a06f2eafc5381cad67eead5')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_46f25a9cf86aed2a1720');
            $table->index('account_id', 'ix_6c4eafba58584c92d29c2707');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_join_as_peers_join_as_peers__users');
        Schema::dropIfExists('tl_phone_join_as_peers_join_as_peers__chats');
        Schema::dropIfExists('tl_phone_join_as_peers_join_as_peers__peers');
        Schema::dropIfExists('tl_phone_join_as_peers_join_as_peers');
    }
};
