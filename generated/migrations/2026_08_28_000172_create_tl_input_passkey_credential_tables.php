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
        Schema::create('tl_input_passkey_credential_input_passkey_cre_f2c081028727', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('pnv_token')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4425ddad6e4da885b73d7e77');
            $table->index('account_id', 'ix_352eae5736350b0c7b2a602d');
        });
        Schema::create('tl_input_passkey_credential_input_passkey_cre_2ab4607fcbc0', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tl_id')->nullable();
            $table->text('raw_id')->nullable();
            $table->bigInteger('response')->nullable();
            $table->index('response', 'ix_574737ae710e93e62c08f1ef');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a1053442fce20bfb465f20fb');
            $table->index('account_id', 'ix_c9613e5a54b135cb42909469');
            $table->unique(['account_id'], 'ux_01e87482b801af2bd41f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_passkey_credential_input_passkey_cre_2ab4607fcbc0');
        Schema::dropIfExists('tl_input_passkey_credential_input_passkey_cre_f2c081028727');
    }
};
