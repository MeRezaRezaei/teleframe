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
        Schema::create('tl_emoji_list_emoji_list', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_47b8420dabbc361f673e7c86');
            $table->index('account_id', 'ix_ceb88ed316a1e0bbb8d21ceb');
        });
        Schema::create('tl_emoji_list_emoji_list__document_id', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_emoji_list_emoji_list', 'id', 'fk_66b2882c47cc9c7613e22613')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_26b260c73390406b3446');
            $table->index('account_id', 'ix_ee1eac8d9ec25d311299edf5');
        });
        Schema::create('tl_emoji_list_emoji_list_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_542b16b930a4c27944740f88');
            $table->index('account_id', 'ix_6aebbcccf05eafb629d2a4ad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_emoji_list_emoji_list_not_modified');
        Schema::dropIfExists('tl_emoji_list_emoji_list__document_id');
        Schema::dropIfExists('tl_emoji_list_emoji_list');
    }
};
