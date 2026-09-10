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
        Schema::create('tl_messages_stickers_stickers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_76538c84630181515c841455');
            $table->index('account_id', 'ix_18dd427bf4c0ebbc5e05edaa');
        });
        Schema::create('tl_messages_stickers_stickers__stickers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_stickers_stickers', 'id', 'fk_886a0e090a961b08cc451b25')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_05743f8804ce44c71ff4');
            $table->index('account_id', 'ix_09beca342915f21e8e3db8cf');
        });
        Schema::create('tl_messages_stickers_stickers_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0ae4f140a1860a0922866a16');
            $table->index('account_id', 'ix_8c64820064d273f69762bf42');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_stickers_stickers_not_modified');
        Schema::dropIfExists('tl_messages_stickers_stickers__stickers');
        Schema::dropIfExists('tl_messages_stickers_stickers');
    }
};
