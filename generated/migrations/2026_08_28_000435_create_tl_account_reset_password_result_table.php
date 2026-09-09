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
        Schema::create('tl_account_reset_password_result', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_1e989fe72c0c403508359808');
            $table->index('account_id', 'ix_1c5750f1ab8429c653820e43');
        });
        Schema::create('tl_account_reset_password_result_reset_passwo_b06ef6c44b97', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_reset_password_result')->cascadeOnDelete();
            $table->integer('retry_date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d201fd0aaabe54b3823ab0dd');
        });
        Schema::create('tl_account_reset_password_result_reset_password_ok', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_reset_password_result')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d0d7e16a02ce65c56ea1256a');
        });
        Schema::create('tl_account_reset_password_result_reset_passwo_87f50999585d', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_reset_password_result')->cascadeOnDelete();
            $table->integer('until_date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f439d256cf0f9253485c9558');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_reset_password_result_reset_passwo_87f50999585d');
        Schema::dropIfExists('tl_account_reset_password_result_reset_password_ok');
        Schema::dropIfExists('tl_account_reset_password_result_reset_passwo_b06ef6c44b97');
        Schema::dropIfExists('tl_account_reset_password_result');
    }
};
