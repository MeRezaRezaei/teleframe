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
        Schema::create('tl_input_passkey_credential', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_passkey_credential_input_passkey_cre_f2c081028727', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_passkey_credential')->cascadeOnDelete();
            $table->text('pnv_token');
            $table->timestamps();
        });
        Schema::create('tl_input_passkey_credential_input_passkey_cre_2ab4607fcbc0', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_passkey_credential')->cascadeOnDelete();
            $table->text('tl_id');
            $table->text('raw_id');
            $table->uuid('response');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_passkey_credential_input_passkey_cre_2ab4607fcbc0');
        Schema::dropIfExists('tl_input_passkey_credential_input_passkey_cre_f2c081028727');
        Schema::dropIfExists('tl_input_passkey_credential');
    }
};
