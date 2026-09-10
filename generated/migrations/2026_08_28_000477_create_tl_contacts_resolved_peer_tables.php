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
        Schema::create('tl_contacts_resolved_peer_resolved_peer', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_f0ecee8658e2d5bdecebd8a7');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0a2f3f5a0f8db7bc45c8ebb2');
            $table->index('account_id', 'ix_2163969e862bdff73d1d242c');
        });
        Schema::create('tl_contacts_resolved_peer_resolved_peer__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_contacts_resolved_peer_resolved_peer', 'id', 'fk_40a02b7c5f33afee0a5721f5')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b0861add1f5414079aee');
            $table->index('account_id', 'ix_7008713429d4407701672070');
        });
        Schema::create('tl_contacts_resolved_peer_resolved_peer__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_contacts_resolved_peer_resolved_peer', 'id', 'fk_384a8735996c7ee210b8d8d8')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_737d3aed4f343741ed72');
            $table->index('account_id', 'ix_b0633e8b90d56e8ca3150fe2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_contacts_resolved_peer_resolved_peer__users');
        Schema::dropIfExists('tl_contacts_resolved_peer_resolved_peer__chats');
        Schema::dropIfExists('tl_contacts_resolved_peer_resolved_peer');
    }
};
