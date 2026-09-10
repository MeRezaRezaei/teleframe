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
        Schema::create('tl_phone_call_discard_reason_phone_call_disca_f54184aa04dd', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e9d4c69db73109f464172bc4');
            $table->index('account_id', 'ix_58be9b0d6dad07a599d29e49');
        });
        Schema::create('tl_phone_call_discard_reason_phone_call_disca_a0f9fca9390c', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_dfff6fdfed86b0bf883488a3');
            $table->index('account_id', 'ix_811a5016cd06be716dcdb1c4');
        });
        Schema::create('tl_phone_call_discard_reason_phone_call_disca_d9fe56a20c35', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_985ef90cda4ea411e804e44e');
            $table->index('account_id', 'ix_f03f0fb83289de092ed273f5');
        });
        Schema::create('tl_phone_call_discard_reason_phone_call_disca_622a32ffc276', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('slug')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_319ccd8ecf12e6bce4806306');
            $table->index('account_id', 'ix_0cc816cc82ab79ff529398f3');
        });
        Schema::create('tl_phone_call_discard_reason_phone_call_disca_73069d1fca2f', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8140e2b0da9fe44465ce3695');
            $table->index('account_id', 'ix_e405cb377a4b8615b955463b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_call_discard_reason_phone_call_disca_73069d1fca2f');
        Schema::dropIfExists('tl_phone_call_discard_reason_phone_call_disca_622a32ffc276');
        Schema::dropIfExists('tl_phone_call_discard_reason_phone_call_disca_d9fe56a20c35');
        Schema::dropIfExists('tl_phone_call_discard_reason_phone_call_disca_a0f9fca9390c');
        Schema::dropIfExists('tl_phone_call_discard_reason_phone_call_disca_f54184aa04dd');
    }
};
