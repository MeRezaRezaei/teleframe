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
        Schema::create('tl_saved_contact', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_da0f765a2ece8d5ae39acf96');
            $table->index('account_id', 'ix_3c78c198927d5379e22b4feb');
        });
        Schema::create('tl_saved_contact_saved_phone_contact', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_saved_contact')->cascadeOnDelete();
            $table->text('phone');
            $table->text('first_name');
            $table->text('last_name');
            $table->integer('date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4dcc1e94d170a91d7f3f0912');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_saved_contact_saved_phone_contact');
        Schema::dropIfExists('tl_saved_contact');
    }
};
